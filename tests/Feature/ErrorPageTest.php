<?php

namespace Tests\Feature;

use App\Models\Contact;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Contract: specs/003-design-system-shell/contracts/shared-props.md §2.
 *
 * FR-041 requires 403/404/419 to render as styled in-app pages. The status code
 * assertion beside each page assertion is the one that must never be dropped: a
 * 200-with-error-page would break assertForbidden()/assertNotFound() across feature
 * 002's suite (FR-045) and would misreport a denial as a success.
 */
class ErrorPageTest extends TestCase
{
    public function test_a_permission_denial_renders_the_error_page_and_keeps_its_403(): void
    {
        $owner = $this->actingAsSalesRep();
        $othersContact = Contact::factory()->create();

        $this->assertNotSame($owner->id, $othersContact->user_id);

        $response = $this->get("/contacts/{$othersContact->id}");

        $response->assertForbidden();
        $response->assertInertia(fn ($page) => $page
            ->component('Errors/Error')
            ->where('status', 403)
        );
    }

    public function test_a_missing_record_renders_the_error_page_and_keeps_its_404(): void
    {
        $this->actingAsSalesRep();

        $response = $this->get('/contacts/999999');

        $response->assertNotFound();
        $response->assertInertia(fn ($page) => $page
            ->component('Errors/Error')
            ->where('status', 404)
        );
    }

    public function test_an_unknown_url_renders_the_error_page_and_keeps_its_404(): void
    {
        $this->actingAsSalesRep();

        $response = $this->get('/no-such-page');

        $response->assertNotFound();
        $response->assertInertia(fn ($page) => $page->component('Errors/Error'));
    }

    public function test_a_wrong_verb_renders_the_error_page_and_keeps_its_405(): void
    {
        $this->actingAsSalesRep();

        $response = $this->get('/logout');

        $response->assertStatus(405);
        $response->assertInertia(fn ($page) => $page
            ->component('Errors/Error')
            ->where('status', 405)
        );
    }

    /**
     * CSRF verification is skipped while running tests, so an expired session cannot be
     * provoked through a normal request. This drives the responder directly through a
     * throwaway route — routes/web.php is untouched (FR-043).
     */
    public function test_an_expired_session_renders_the_error_page_and_keeps_its_419(): void
    {
        Route::get('/__expired_session_probe', fn () => abort(419))->middleware('web');

        $response = $this->get('/__expired_session_probe');

        $response->assertStatus(419);
        $response->assertInertia(fn ($page) => $page
            ->component('Errors/Error')
            ->where('status', 419)
        );
    }

    public function test_a_guest_reaching_an_error_page_is_offered_the_sign_in_route(): void
    {
        Route::get('/__guest_error_probe', fn () => abort(403))->middleware('web');

        $response = $this->get('/__guest_error_probe');

        $response->assertForbidden();
        $response->assertInertia(fn ($page) => $page
            ->component('Errors/Error')
            ->where('status', 403)
            ->where('auth.user', null)
        );
    }

    /**
     * An unrouted URL is rejected before the `web` middleware group runs, so
     * HandleInertiaRequests never shares auth/flash for it. The error page must still
     * render rather than blowing up on the missing props — it reads them with optional
     * chaining for exactly this case. Consequence worth knowing: on a mistyped URL the
     * page always offers "Go to sign in", even to a signed-in user. That link is still
     * correct-by-accident, since /login is guest-only and redirects them onward.
     */
    public function test_the_error_page_survives_having_no_shared_props_at_all(): void
    {
        $this->actingAsSalesRep();

        $response = $this->get('/no-such-page');

        $response->assertNotFound();
        $response->assertInertia(fn ($page) => $page
            ->component('Errors/Error')
            ->where('status', 404)
            ->missing('auth')
        );
    }
}
