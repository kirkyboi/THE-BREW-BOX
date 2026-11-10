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

class ProductController extends Controller
{
    private function authorizeAdmin(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->status === 'active' &&
            DB::table('roles')->where('id', $user->role_id)->value('slug') === 'admin', 403);
    }

    public function index(Request $request): View
    {
        $this->authorizeAdmin();
        $query = DB::table('products as p')->join('categories as c','c.id','=','p.category_id')
            ->whereNull('p.deleted_at')
            ->select('p.*','c.name as category_name');
        if ($request->filled('search')) {
            $term = trim((string)$request->query('search'));
            $query->where(fn($q) => $q->where('p.name','like',"%{$term}%")
                ->orWhere('p.sku','like',"%{$term}%")
                ->orWhere('p.description','like',"%{$term}%"));
        }
        if ($request->filled('category') && ctype_digit((string)$request->query('category'))) {
            $query->where('p.category_id',(int)$request->query('category'));
        }
        if (in_array($request->query('status'),['active','inactive','unavailable','featured'],true)) {
            match ($request->query('status')) {
                'active' => $query->where('p.is_active',1),
                'inactive' => $query->where('p.is_active',0),
                'unavailable' => $query->where('p.is_available',0),
                'featured' => $query->where('p.is_featured',1),
            };
        }
        $products = $query->orderByDesc('p.id')->paginate(10)->withQueryString();
        $categories = DB::table('categories')->orderBy('sort_order')->orderBy('name')->get();
        $counts = [
            'total' => DB::table('products')->whereNull('deleted_at')->count(),
            'active' => DB::table('products')->whereNull('deleted_at')->where('is_active',1)->count(),
            'featured' => DB::table('products')->whereNull('deleted_at')->where('is_featured',1)->count(),
            'unavailable' => DB::table('products')->whereNull('deleted_at')->where('is_available',0)->count(),
        ];
        return view('admin.products.index',compact('products','categories','counts'));
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name'=>['required','string','max:150'],
            'sku'=>['required','string','max:60',Rule::unique('products','sku')->ignore($id)],
            'category_id'=>['required','integer',Rule::exists('categories','id')],
            'description'=>['nullable','string','max:5000'],
            'base_price'=>['required','numeric','min:0','max:9999999999'],
            'preparation_minutes'=>['required','integer','min:0','max:65535'],
            'image'=>['nullable','image','mimes:jpeg,png,webp','max:4096'],
            'is_active'=>['nullable','boolean'],
            'is_available'=>['nullable','boolean'],
            'is_featured'=>['nullable','boolean'],
        ]);
    }

    private function uniqueSlug(string $name, ?int $ignore = null): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $i = 2;
        while (DB::table('products')->where('slug',$slug)
            ->when($ignore,fn($q)=>$q->where('id','!=',$ignore))->exists()) {
            $slug = $base.'-'.$i++;
        }
        return $slug;
    }

    private function fields(array $validated, Request $request): array
    {
        return [
            'name'=>$validated['name'], 'sku'=>$validated['sku'],
            'category_id'=>(int)$validated['category_id'],
            'description'=>$validated['description'] ?? null,
            'base_price'=>$validated['base_price'],
            'preparation_minutes'=>(int)$validated['preparation_minutes'],
            'is_active'=>$request->boolean('is_active'),
            'is_available'=>$request->boolean('is_available'),
            'is_featured'=>$request->boolean('is_featured'),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();
        $validated=$this->validated($request);
        $data=$this->fields($validated,$request);
        $data['slug']=$this->uniqueSlug($data['name']);
        if ($request->hasFile('image')) $data['image']=$request->file('image')->store('products','public');
        $data['created_at']=now();
        $data['updated_at']=now();
        try {
            DB::table('products')->insert($data);
        } catch (\Throwable $e) {
            if (!empty($data['image'])) Storage::disk('public')->delete($data['image']);
            throw $e;
        }
        return redirect()->route('admin.products.index')->with('success','Product created successfully.');
    }

    public function update(Request $request, int $product): RedirectResponse
    {
        $this->authorizeAdmin();
        $existing=DB::table('products')->where('id',$product)->whereNull('deleted_at')->first();
        abort_unless($existing,404);
        $validated=$this->validated($request,$product);
        $data=$this->fields($validated,$request);
        $data['slug']=$this->uniqueSlug($data['name'],$product);
        $data['updated_at']=now();
        $newImage=null;
        if ($request->hasFile('image')) {
            $newImage=$request->file('image')->store('products','public');
            $data['image']=$newImage;
        }
        try {
            DB::table('products')->where('id',$product)->update($data);
        } catch (\Throwable $e) {
            if ($newImage) Storage::disk('public')->delete($newImage);
            throw $e;
        }
        if ($newImage && $existing->image && !Str::startsWith($existing->image,['http://','https://'])) {
            Storage::disk('public')->delete(preg_replace('#^(storage/|public/)#','',$existing->image));
        }
        return redirect()->route('admin.products.index')->with('success','Product updated successfully.');
    }

    public function destroy(int $product): RedirectResponse
    {
        $this->authorizeAdmin();
        abort_unless(DB::table('products')->where('id',$product)->whereNull('deleted_at')->exists(),404);
        DB::table('products')->where('id',$product)->update(['deleted_at'=>now(),'is_active'=>0,'is_available'=>0,'updated_at'=>now()]);
        return redirect()->route('admin.products.index')->with('success','Product archived successfully.');
    }
}
