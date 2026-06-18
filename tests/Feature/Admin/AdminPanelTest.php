<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\AffiliateNetwork;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
    }

    public function test_guest_is_redirected_from_admin(): void
    {
        $this->get('/admin')->assertRedirect();
    }

    public function test_plain_user_cannot_access_admin(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_editor_can_access_admin(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor, 'is_active' => true]);

        $this->actingAs($editor)->get('/admin')->assertSuccessful();
    }

    public function test_editor_can_manage_coupons_and_blog_but_not_admin_only_resources(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor, 'is_active' => true]);

        // Allowed for editors
        $this->actingAs($editor)->get('/admin/coupons')->assertSuccessful();
        $this->actingAs($editor)->get('/admin/blog-posts')->assertSuccessful();

        // Admin-only resources are forbidden for editors
        $this->actingAs($editor)->get('/admin/users')->assertForbidden();
        $this->actingAs($editor)->get('/admin/affiliate-networks')->assertForbidden();
        $this->actingAs($editor)->get('/admin/stores')->assertForbidden();
    }

    public function test_deactivated_admin_cannot_access(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin, 'is_active' => false]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function resourcePaths(): array
    {
        return [
            'stores index' => ['/admin/stores'],
            'stores create' => ['/admin/stores/create'],
            'coupons index' => ['/admin/coupons'],
            'coupons create' => ['/admin/coupons/create'],
            'categories index' => ['/admin/categories'],
            'categories create' => ['/admin/categories/create'],
            'networks index' => ['/admin/affiliate-networks'],
            'networks create' => ['/admin/affiliate-networks/create'],
            'blog index' => ['/admin/blog-posts'],
            'blog create' => ['/admin/blog-posts/create'],
            'newsletter index' => ['/admin/newsletter-subscribers'],
            'users index' => ['/admin/users'],
            'users create' => ['/admin/users/create'],
        ];
    }

    #[DataProvider('resourcePaths')]
    public function test_admin_resource_pages_render(string $path): void
    {
        $this->actingAs($this->admin())->get($path)->assertSuccessful();
    }

    public function test_store_edit_page_renders_with_translations(): void
    {
        $store = Store::factory()->create();

        $this->actingAs($this->admin())
            ->get('/admin/stores/'.$store->getRouteKey().'/edit')
            ->assertSuccessful();
    }

    public function test_coupon_edit_page_renders(): void
    {
        $coupon = Coupon::factory()->for(Store::factory())->create();

        $this->actingAs($this->admin())
            ->get('/admin/coupons/'.$coupon->getRouteKey().'/edit')
            ->assertSuccessful();
    }

    public function test_category_and_network_and_blog_edit_pages_render(): void
    {
        $category = Category::factory()->create();
        $network = AffiliateNetwork::factory()->create();
        $post = BlogPost::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/categories/'.$category->getRouteKey().'/edit')->assertSuccessful();
        $this->actingAs($admin)->get('/admin/affiliate-networks/'.$network->getRouteKey().'/edit')->assertSuccessful();
        $this->actingAs($admin)->get('/admin/blog-posts/'.$post->getRouteKey().'/edit')->assertSuccessful();
    }
}
