<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View as ViewResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Superadmin announcement management.
 * Expected table: announcements (id, title, content, status, created_at, updated_at).
 * Optional columns: published_at, created_by, updated_by.
 */
final class AnnouncementController extends Controller
{
    private function authorizeSuperadmin(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->status === 'active', 403, 'Your account is not active.');

        $role = DB::table('roles')->where('id', $user->role_id)->value('slug');
        abort_unless($role === 'superadmin', 403, 'Superadmin access required.');
    }

    private function ensureTable(): void
    {
        if (! Schema::hasTable('announcements')) {
            throw new HttpException(503, 'Announcements table is missing. Run the announcements migration first.');
        }
    }

    private function getAnnouncement(int $id): object
    {
        $this->ensureTable();
        return DB::table('announcements')->where('id', $id)->whereNull('deleted_at')->first() ?? abort(404);
    }

    private function optionalAuditFields(array $data, bool $creating): array
    {
        if ($creating && Schema::hasColumn('announcements', 'created_by')) {
            $data['created_by'] = Auth::id();
        }
        if (Schema::hasColumn('announcements', 'updated_by')) {
            $data['updated_by'] = Auth::id();
        }
        return $data;
    }

    public function index(): ViewResponse
    {
        $this->authorizeSuperadmin();
        $this->ensureTable();
        $announcements = DB::table('announcements')->whereNull('deleted_at')->orderByDesc('id')->paginate(12);
        $selectedMode = request()->query('mode');
        $selectedId = (int) request()->query('announcement', 0);
        $selectedAnnouncement = in_array($selectedMode, ['view','edit'], true) && $selectedId > 0
            ? $this->getAnnouncement($selectedId) : null;
        return view('superadmin.announcements.index', compact('announcements','selectedMode','selectedAnnouncement'));
    }

    public function create(): ViewResponse
    {
        $this->authorizeSuperadmin();
        return redirect()->route('superadmin.announcements.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperadmin();
        $this->ensureTable();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'content' => ['required', 'string', 'max:20000'],
            'status' => ['required', Rule::in(['draft', 'published', 'scheduled', 'archived'])],
            'type' => ['nullable', Rule::in(['general', 'promotion', 'event', 'maintenance', 'important'])],
            'target_audience' => ['nullable', Rule::in(['all', 'customers', 'staff'])],
            'is_pinned' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);
        $uploadedImage = $request->file('image');
        unset($data['image']);
        $data['type'] = $data['type'] ?? 'general';
        $data['target_audience'] = $data['target_audience'] ?? 'all';
        $data['is_pinned'] = (bool) ($data['is_pinned'] ?? false);
        $baseSlug = Str::slug($data['title']) ?: 'announcement';
        $slug = Str::limit($baseSlug, 170, '');
        $candidate = $slug;
        $counter = 2;
        while (DB::table('announcements')->where('slug', $candidate)->exists()) {
            $candidate = Str::limit($slug, 170 - strlen((string)$counter) - 1, '') . '-' . $counter++;
        }
        $data['slug'] = $candidate;
        $now = now();
        if (Schema::hasColumn('announcements', 'created_at')) $data['created_at'] = $now;
        if (Schema::hasColumn('announcements', 'updated_at')) $data['updated_at'] = $now;
        if (Schema::hasColumn('announcements', 'published_at') && $data['status'] === 'published') {
            $data['published_at'] = $now;
        }
        if ($uploadedImage) {
            $data['image_path'] = $uploadedImage->store('announcements', 'public');
        }
        try {
            DB::table('announcements')->insert($this->optionalAuditFields($data, true));
        } catch (\Throwable $e) {
            if (!empty($data['image_path'])) Storage::disk('public')->delete($data['image_path']);
            throw $e;
        }
        return redirect()->route('superadmin.announcements.index')->with('success', 'Announcement created successfully.');
    }

    
public function show(int $announcement): RedirectResponse
{
    $this->authorizeSuperadmin();

    $announcement = $this->getAnnouncement($announcement);

    return redirect()->route(
        'superadmin.announcements.index',
        [
            'mode' => 'view',
            'announcement' => $announcement->id,
        ]
    );
}

public function edit(int $announcement): RedirectResponse
{
    $this->authorizeSuperadmin();

    $announcement = $this->getAnnouncement($announcement);

    return redirect()->route(
        'superadmin.announcements.index',
        [
            'mode' => 'edit',
            'announcement' => $announcement->id,
        ]
    );
}


    public function update(Request $request, int $announcement): RedirectResponse
    {
        $this->authorizeSuperadmin();
        $existing = $this->getAnnouncement($announcement);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'content' => ['required', 'string', 'max:20000'],
            'status' => ['required', Rule::in(['draft', 'published', 'scheduled', 'archived'])],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
            'type' => ['required', Rule::in(['general', 'promotion', 'event', 'maintenance', 'important'])],
            'target_audience' => ['required', Rule::in(['all', 'customers', 'staff'])],
            'is_pinned' => ['required', 'boolean'],
        ]);
        unset($data['image'], $data['remove_image']);
        $newImagePath = null;
        if ($request->hasFile('image')) {
            $newImagePath = $request->file('image')->store('announcements', 'public');
            $data['image_path'] = $newImagePath;
        } elseif ($request->boolean('remove_image')) {
            $data['image_path'] = null;
        }
        if (Schema::hasColumn('announcements', 'updated_at')) $data['updated_at'] = now();
        if (Schema::hasColumn('announcements', 'published_at')) {
            if ($data['status'] === 'published' && $existing->status !== 'published') $data['published_at'] = now();
            if ($data['status'] === 'draft') $data['published_at'] = null;
        }
        try {
            DB::table('announcements')->where('id', $announcement)->update($this->optionalAuditFields($data, false));
        } catch (\Throwable $e) {
            if ($newImagePath) Storage::disk('public')->delete($newImagePath);
            throw $e;
        }
        if (!empty($existing->image_path) && array_key_exists('image_path', $data)) Storage::disk('public')->delete($existing->image_path);
        return redirect()->route('superadmin.announcements.index')->with('success', 'Announcement updated successfully.');
    }

    public function publish(int $announcement): RedirectResponse
    {
        $this->authorizeSuperadmin();
        $this->getAnnouncement($announcement);
        $data = ['status' => 'published'];
        if (Schema::hasColumn('announcements', 'updated_at')) $data['updated_at'] = now();
        if (Schema::hasColumn('announcements', 'published_at')) $data['published_at'] = now();
        DB::table('announcements')->where('id', $announcement)->update($this->optionalAuditFields($data, false));
        return redirect()->route('superadmin.announcements.index')->with('success', 'Announcement published.');
    }

    public function destroy(int $announcement): RedirectResponse
    {
        $this->authorizeSuperadmin();
        $existing = $this->getAnnouncement($announcement);
        DB::table('announcements')->where('id', $announcement)->update(['deleted_at' => now(), 'updated_at' => now()]);
        if (!empty($existing->image_path)) Storage::disk('public')->delete($existing->image_path);
        return redirect()->route('superadmin.announcements.index')->with('success', 'Announcement deleted.');
    }
}
