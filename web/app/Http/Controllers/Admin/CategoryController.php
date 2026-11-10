<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    private function authorizeAdmin(): void
    {
        $user = Auth::user();
        abort_unless(
            $user && $user->status === 'active'
            && DB::table('roles')->where('id', $user->role_id)->value('slug') === 'admin',
            403,
            'Only active administrators can manage categories.'
        );
    }

    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $query = DB::table('categories as c')
            ->leftJoin('products as p', function ($join) {
                $join->on('p.category_id', '=', 'c.id')->whereNull('p.deleted_at');
            })
            ->select('c.*')
            ->selectRaw('COUNT(p.id) as products_count')
            ->groupBy('c.id', 'c.name', 'c.slug', 'c.description', 'c.image', 'c.sort_order', 'c.is_active', 'c.created_at', 'c.updated_at');

        if ($request->filled('search')) {
            $term = trim((string) $request->query('search'));
            $query->where(function ($q) use ($term) {
                $q->where('c.name', 'like', "%{$term}%")
                    ->orWhere('c.slug', 'like', "%{$term}%")
                    ->orWhere('c.description', 'like', "%{$term}%");
            });
        }

        if ($request->query('status') === 'active') {
            $query->where('c.is_active', 1);
        } elseif ($request->query('status') === 'inactive') {
            $query->where('c.is_active', 0);
        }

        $categories = $query->orderBy('c.sort_order')->orderBy('c.name')
            ->paginate(10)->withQueryString();

        $counts = [
            'total' => DB::table('categories')->count(),
            'active' => DB::table('categories')->where('is_active', 1)->count(),
            'inactive' => DB::table('categories')->where('is_active', 0)->count(),
            'products' => DB::table('products')->whereNull('deleted_at')->count(),
        ];

        return view('admin.categories.index', compact('categories', 'counts'));
    }

    private function validated(Request $request, ?int $categoryId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('categories', 'name')->ignore($categoryId)],
            'description' => ['nullable', 'string', 'max:3000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $suffix = 2;
        while (DB::table('categories')->where('slug', $slug)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base . '-' . $suffix++;
        }
        return $slug;
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();
        $validated = $this->validated($request);
        $image = $request->hasFile('image')
            ? $request->file('image')->store('categories', 'public') : null;

        try {
            DB::table('categories')->insert([
                'name' => trim($validated['name']),
                'slug' => $this->uniqueSlug($validated['name']),
                'description' => $validated['description'] ?? null,
                'image' => $image,
                'sort_order' => (int) $validated['sort_order'],
                'is_active' => $request->boolean('is_active'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            if ($image) Storage::disk('public')->delete($image);
            throw $e;
        }

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function update(Request $request, int $category): RedirectResponse
    {
        $this->authorizeAdmin();
        $existing = DB::table('categories')->where('id', $category)->first();
        abort_unless($existing, 404);
        $validated = $this->validated($request, $category);

        $newImage = $request->hasFile('image')
            ? $request->file('image')->store('categories', 'public') : null;

        $data = [
            'name' => trim($validated['name']),
            'slug' => $this->uniqueSlug($validated['name'], $category),
            'description' => $validated['description'] ?? null,
            'sort_order' => (int) $validated['sort_order'],
            'is_active' => $request->boolean('is_active'),
            'updated_at' => now(),
        ];
        if ($newImage) $data['image'] = $newImage;

        try {
            DB::table('categories')->where('id', $category)->update($data);
        } catch (\Throwable $e) {
            if ($newImage) Storage::disk('public')->delete($newImage);
            throw $e;
        }

        if ($newImage && $existing->image && Str::startsWith($existing->image, 'categories/')) {
            Storage::disk('public')->delete($existing->image);
        }

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(int $category): RedirectResponse
    {
        $this->authorizeAdmin();
        $existing = DB::table('categories')->where('id', $category)->first();
        abort_unless($existing, 404);

        $linked = DB::table('products')->where('category_id', $category)->exists();
        if ($linked) {
            return redirect()->route('admin.categories.index')->with('error',
                'This category has products assigned to it. Move or remove those products before deleting the category.');
        }

        DB::table('categories')->where('id', $category)->delete();
        if ($existing->image && Str::startsWith($existing->image, 'categories/')) {
            Storage::disk('public')->delete($existing->image);
        }
        return redirect()->route('admin.categories.index')->with('success', 'Category deleted successfully.');
    }
}
