<?php

namespace App\Http\Controllers;

use Auth;
use Session;
use App\Acces;
use App\Supply;
use App\Product;
use App\Transaction;
use App\Supply_system;
use App\Imports\ProductImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class ProductManageController extends Controller
{
    // Show View Product
    public function viewProduct()
    {
        $id_account = Auth::id();
        $check_access = Acces::where('user', $id_account)
        ->first();
        if($check_access->kelola_barang == 1){
        	$products = Product::all()
            ->sortBy('kode_barang');
            $supply_system = Supply_system::first();

        	return view('manage_product.product', compact('products', 'supply_system'));
        }else{
            return back();
        }
    }

    // Show View New Product
    public function viewNewProduct()
    {
        $id_account = Auth::id();
        $check_access = Acces::where('user', $id_account)
        ->first();
        if($check_access->kelola_barang == 1){
            $supply_system = Supply_system::first();

        	return view('manage_product.new_product', compact('supply_system'));
        }else{
            return back();
        }
    }

    // Filter Product Table
    public function filterTable($id)
    {
        $id_account = Auth::id();
        $check_access = Acces::where('user', $id_account)
        ->first();
        if($check_access->kelola_barang == 1){
            $supply_system = Supply_system::first();
            $products = Product::orderBy($id, 'asc')
            ->get();

            return view('manage_product.filter_table.table_view', compact('products', 'supply_system'));
        }else{
            return back();
        }
    }

    // Create New Product
    public function createProduct(Request $req)
    {
        $id_account = Auth::id();
        $check_access = Acces::where('user', $id_account)->first();
        if ($check_access->kelola_barang != 1) {
            return back();
        }

        $req->validate([
            'nama_barang' => 'required|string|max:255',
            // validasi field lain sesuai kebutuhan
        ]);

        $nama = $req->nama_barang;
        $hurufPertama = strtoupper(substr($nama, 0, 1));

        // Cari kode barang terakhir dengan prefix huruf yang sama, urut descending
        $lastProduct = Product::where('kode_barang', 'like', $hurufPertama . '%')
            ->orderBy('kode_barang', 'desc')
            ->first();

        if ($lastProduct) {
            $lastNumber = intval(substr($lastProduct->kode_barang, 1));
            $nomorUrut = $lastNumber + 1;
        } else {
            $nomorUrut = 1;
        }

        $nomorFormatted = str_pad($nomorUrut, 6, '0', STR_PAD_LEFT);
        $kodeBarangBaru = $hurufPertama . $nomorFormatted;

        // Cek duplikat kode barang sebelum simpan (optional)
        $exists = Product::where('kode_barang', $kodeBarangBaru)->exists();
        if ($exists) {
            Session::flash('create_failed', 'Kode barang sudah ada, coba lagi.');
            return back()->withInput();
        }

        // Simpan data produk
        $product = new Product;
        $product->kode_barang = $kodeBarangBaru;
        $product->nama_barang = $nama;
        $product->kategori = $req->kategori ?? null;
        $product->satuan = $req->satuan ?? null;
        $product->merek = $req->merek ?? null;

        $supply_system = Supply_system::first();
        if ($supply_system && $supply_system->status == true) {
            $product->stok = $req->stok ?? 0;
        } else {
            $product->stok = 1;
        }

        $product->harga = $req->harga ?? 0;

        try {
            $product->save();
        } catch (\Exception $e) {
            Session::flash('create_failed', 'Terjadi kesalahan saat menyimpan data.');
            return back()->withInput();
        }

        Session::flash('create_success', 'Barang berhasil ditambahkan dengan kode ' . $kodeBarangBaru);
        return redirect('/product');
    }

    public function generateKode($prefix)
    {
        $prefix = strtoupper($prefix);
        $last = Product::where('kode_barang', 'like', $prefix . '%')
                    ->orderBy('kode_barang', 'desc')
                    ->first();

        if ($last) {
            $lastNumber = intval(substr($last->kode_barang, 1));
            $nextNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '000001';
        }

        return response()->json(['kode' => $prefix . $nextNumber]);
    }

    // Import New Product
    public function importProduct(Request $req)
    {
        $id_account = Auth::id();
        $check_access = Acces::where('user', $id_account)
        ->first();
        if($check_access->kelola_barang == 1){
        	try{
        		$file = $req->file('excel_file');
    			$nama_file = rand().$file->getClientOriginalName();
    			$file->move('excel_file', $nama_file);
    			Excel::import(new ProductImport, public_path('/excel_file/'.$nama_file));

    			Session::flash('import_success', 'Data barang berhasil diimport');
        	}catch(\Exception $ex){
        		Session::flash('import_failed', 'Cek kembali terdapat data kosong atau kode barang yang telah tersedia');

        		return back();
        	}

        	return redirect('/product');
        }else{
            return back();
        }
    }

    // Edit Product
    public function editProduct($id)
    {
        $id_account = Auth::id();
        $check_access = Acces::where('user', $id_account)
        ->first();
        if($check_access->kelola_barang == 1){
            $product = Product::find($id);

            return response()->json(['product' => $product]);
        }else{
            return back();
        }
    }

    // Update Product
    public function updateProduct(Request $req)
    {
        $id_account = Auth::id();
        $check_access = Acces::where('user', $id_account)
        ->first();
        if($check_access->kelola_barang == 1){
            $check_product = Product::where('kode_barang', $req->kode_barang)
            ->count();
            $product_data = Product::find($req->id);
            if($check_product == 0 || $product_data->kode_barang == $req->kode_barang)
            {
                $product = Product::find($req->id);
                $kode_barang = $product->kode_barang;
                $product->kode_barang = $req->kode_barang;
                $product->kategori = $req->kategori;
                $product->nama_barang = $req->nama_barang;
                $product->satuan = $req->satuan;
                $product->merek = $req->merek;
                $product->stok = $req->stok;
                $product->harga = $req->harga;
                if($req->stok <= 0)
                {
                    $product->keterangan = "Habis";
                }else{
                    $product->keterangan = "Tersedia";
                }
                $product->save();

                Supply::where('kode_barang', $kode_barang)
                ->update(['kode_barang' => $req->kode_barang]);
                Transaction::where('kode_barang', $kode_barang)
                ->update(['kode_barang' => $req->kode_barang]);

                Session::flash('update_success', 'Data barang berhasil diubah');

                return redirect('/product');
            }else{
                Session::flash('update_failed', 'Kode barang telah digunakan');

                return back();
            }
        }else{
            return back();
        }
    }

    // Delete Product
    public function deleteProduct($id)
    {
        $id_account = Auth::id();
        $check_access = Acces::where('user', $id_account)
        ->first();
        if($check_access->kelola_barang == 1){
            Product::destroy($id);

            Session::flash('delete_success', 'Barang berhasil dihapus');

            return back();
        }else{
            return back();
        }
    }
}
