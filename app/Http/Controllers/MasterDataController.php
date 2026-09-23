<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman: Data Master (Admin)
 * Tampilan: resources/views/master/index.blade.php
 * Mengelola daftar Lokasi/Ruang dan Kategori Kerusakan yang dipakai
 * sebagai pilihan dropdown di halaman Ajukan Laporan.
 */
class MasterDataController extends Controller
{
    public function index(): View
    {
        $locations = Location::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('master.index', compact('locations', 'categories'));
    }

    public function storeLocation(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        Location::create($data);

        return back()->with('success', 'Lokasi baru ditambahkan.');
    }

    public function destroyLocation(Location $location): RedirectResponse
    {
        $location->delete();

        return back()->with('success', 'Lokasi dihapus.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        Category::create($data);

        return back()->with('success', 'Kategori baru ditambahkan.');
    }

    public function destroyCategory(Category $category): RedirectResponse
    {
        $category->delete();

        return back()->with('success', 'Kategori dihapus.');
    }
}
