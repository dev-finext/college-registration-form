<?php

namespace Tests\Feature;

use App\Mail\RegistrationSubmitted;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function payload(array $overrides = []): array
    {
        $data = require base_path('tests/Fixtures/registration.php');

        // As the browser sends it: formatted phone, booleans, honeypot.
        return array_merge($data, ['mobile' => '050-5773045', 'website' => ''], $overrides);
    }

    public function test_form_page_renders_without_recipient_emails(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('טופס רישום להנדסאים')
            ->assertSee('שכר הלימוד במלואו (שייקבע בנהלי מה"ט) + 200 ₪ דמי פתיחת תיק ורישום')
            ->assertDontSee('@example.org');
    }

    public function test_branch_link_locks_the_branch(): void
    {
        $this->get('/haifa')->assertOk()->assertSee('"lockedBranch":"haifa"', false);
        $this->get('/no-such-branch')->assertNotFound();
    }

    public function test_branches_and_majors_follow_the_college_list(): void
    {
        $branches = config('registration.branches');

        $this->assertSame(['haifa', 'petah-tikva', 'rishon', 'ashdod', 'jerusalem', 'eilat'], array_keys($branches));
        $this->assertCount(8, $branches['haifa']['majors']);
        $this->assertSame(['אדריכלות ועיצוב פנים'], $branches['eilat']['majors']);
        $this->assertNotContains('תוכנה', $branches['jerusalem']['majors']);
    }

    public function test_every_branch_has_its_own_link(): void
    {
        foreach (array_keys(config('registration.branches')) as $branch) {
            $this->get("/{$branch}")->assertOk()->assertSee('"lockedBranch":"'.$branch.'"', false);
        }
    }

    public function test_token_endpoint_returns_a_fresh_csrf_token(): void
    {
        $this->getJson('/token')->assertOk()->assertJsonStructure(['token']);
    }

    public function test_valid_registration_emails_pdf_to_all_branch_addresses(): void
    {
        $this->postJson('/register', $this->payload())
            ->assertOk()
            ->assertJson(['ok' => true, 'branch' => 'ירושלים']);

        Mail::assertSent(RegistrationSubmitted::class, function (RegistrationSubmitted $mail) {
            return $mail->hasTo('manager.jerusalem@example.org')
                && $mail->hasSubject('טופס רישום הנדסאים - ענת וולפמן - חשמל')
                && $mail->hasReplyTo('anat@example.com')
                && str_starts_with($mail->pdf, '%PDF')
                && $mail->data['mobile'] === '0505773045';
        });
    }

    public function test_branch_with_several_addresses_gets_one_email_to_all_of_them(): void
    {
        $this->postJson('/register', $this->payload(['branch' => 'haifa', 'major' => 'מכונות']))->assertOk();

        Mail::assertSent(RegistrationSubmitted::class, fn ($mail) => $mail->hasTo('manager.haifa@example.org') && $mail->hasTo('secretary.haifa@example.org'));
        Mail::assertSentCount(1);
    }

    public function test_rejects_id_with_wrong_check_digit(): void
    {
        $this->postJson('/register', $this->payload(['idNumber' => '011112222']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['idNumber' => 'ספרת ביקורת']);

        Mail::assertNothingSent();
    }

    public function test_rejects_major_not_offered_by_branch(): void
    {
        // Jerusalem has no mechanical engineering; Haifa and Ashdod do.
        $this->postJson('/register', $this->payload(['branch' => 'jerusalem', 'major' => 'מכונות']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('major');
    }

    public function test_checks_date_consistency(): void
    {
        $this->postJson('/register', $this->payload(['aliyahDate' => '1991-09-01']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('aliyahDate');

        $this->postJson('/register', $this->payload(['releaseDate' => '2015-01-01']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('releaseDate');
    }

    public function test_requires_commitment_and_signature(): void
    {
        $this->postJson('/register', $this->payload(['commitmentAccepted' => false, 'signature' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['commitmentAccepted', 'signature']);
    }

    public function test_conditional_fields_are_dropped(): void
    {
        $this->postJson('/register', $this->payload([
            'birthCountry' => 'ישראל',
            'aliyahDate' => '1991-01-01',
            'serviceType' => 'לא',
            'enlistDate' => '2016-08-01',
        ]))->assertOk();

        Mail::assertSent(RegistrationSubmitted::class, fn ($mail) => $mail->data['aliyahDate'] === null
            && $mail->data['enlistDate'] === null
            && $mail->data['personalNumber'] === null);
    }

    public function test_honeypot_returns_success_without_sending(): void
    {
        $this->postJson('/register', $this->payload(['website' => 'http://spam.example']))->assertOk();

        Mail::assertNothingSent();
    }

    public function test_blocks_resending_the_same_registration(): void
    {
        $this->postJson('/register', $this->payload())->assertOk();
        $this->postJson('/register', $this->payload())->assertStatus(409);

        Mail::assertSentCount(1);
    }

    public function test_rate_limits_by_ip(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/register', $this->payload(['idNumber' => '011112222']))->assertUnprocessable();
        }

        $this->postJson('/register', $this->payload())->assertStatus(429);
    }
}
