<?php

namespace App\Http\Controllers;

use Session;
use Carbon\Carbon;
use App\Market;
use App\Transaction;
use Illuminate\Http\Request;

class ViewManageController extends Controller
{
    // Show View Dashboard
   public function viewDashboard()
{
    // Ambil 5 transaksi terakhir berdasarkan kode transaksi
    $kd_transaction = Transaction::select('kode_transaksi')
        ->latest()
        ->distinct()
        ->take(5)
        ->get();

    // Ambil semua transaksi dan ekstrak tanggal unik
    $transactions = Transaction::all();
    $dates = $transactions->pluck('created_at')->map(function ($date) {
        return $date->toDateString();
    })->unique()->sortDesc()->values()->all();

    // Ambil 7 tanggal terakhir untuk grafik
    $incomes_data = array_slice($dates, 0, 7);
    $incomes = array_reverse($incomes_data); // urut dari lama ke baru

    // ================= GRAFIK TOTAL PER HARI (1x per kode_transaksi) =================
    $chart_totals = [];
    foreach ($incomes as $date) {
        $kode_transaksi_harian = Transaction::whereDate('created_at', $date)
            ->select('kode_transaksi')
            ->distinct()
            ->get();

        $total = 0;
        foreach ($kode_transaksi_harian as $kode) {
            $transaksi = Transaction::where('kode_transaksi', $kode->kode_transaksi)->first();
            if ($transaksi) {
                $total += $transaksi->total;
            }
        }

        $chart_totals[] = $total;
    }
    // ===============================================================================

    // Tanggal awal dan akhir bulan sekarang
    $startOfMonth = Carbon::now()->startOfMonth();
    $endOfMonth = Carbon::now()->endOfMonth();

    // ================= TOTAL PEMASUKAN BULAN INI (1x per kode_transaksi) ==============
    $unique_transactions = Transaction::whereBetween('created_at', [$startOfMonth, $endOfMonth])
        ->select('kode_transaksi')
        ->distinct()
        ->get();

    $all_incomes = 0;
    foreach ($unique_transactions as $kode) {
        $transaksi = Transaction::where('kode_transaksi', $kode->kode_transaksi)->first();
        if ($transaksi) {
            $all_incomes += $transaksi->total;
        }
    }
    // ===============================================================================

    // ================= TOTAL PEMASUKAN HARIAN (1x per kode_transaksi) ================
    $unique_daily_transactions = Transaction::whereDate('created_at', Carbon::now())
        ->select('kode_transaksi')
        ->distinct()
        ->get();

    $incomes_daily = 0;
    foreach ($unique_daily_transactions as $kode) {
        $transaksi = Transaction::where('kode_transaksi', $kode->kode_transaksi)->first();
        if ($transaksi) {
            $incomes_daily += $transaksi->total;
        }
    }
    // ===============================================================================

    // Jumlah pelanggan harian
    $customers_daily = count($unique_daily_transactions);

    // Informasi toko
    $market = Market::first();

    // Rentang tanggal tampilan
    $min_date = $startOfMonth;
    $max_date = $endOfMonth;

    return view('dashboard', compact(
        'kd_transaction',
        'incomes',
        'chart_totals',
        'incomes_daily',
        'customers_daily',
        'all_incomes',
        'min_date',
        'max_date',
        'market',
        'startOfMonth',
        'endOfMonth'
    ));
}

    // Filter Chart Dashboard
public function filterChartDashboard($filter)
{
    $supplies = Transaction::all();
    $array = [];

    foreach ($supplies as $supply) {
        $array[] = $supply->created_at->toDateString();
    }

    $dates = array_unique($array);
    rsort($dates);

    $data_slice = array_slice($dates, 0, 7);
    $dates_sorted = array_reverse($data_slice); // dari lama ke baru

    if ($filter == 'pemasukan') {
        $total = [];
        foreach ($dates_sorted as $date) {
            $kode_transaksi_harian = Transaction::whereDate('created_at', $date)
                ->select('kode_transaksi')
                ->distinct()
                ->get();

            $sum = 0;
            foreach ($kode_transaksi_harian as $kode) {
                $transaksi = Transaction::where('kode_transaksi', $kode->kode_transaksi)->first();
                if ($transaksi) {
                    $sum += $transaksi->total;
                }
            }

            $total[] = $sum;
        }

        return response()->json([
            'incomes' => $dates_sorted,
            'total' => $total
        ]);
    } else {
        $jumlah = [];
        foreach ($dates_sorted as $date) {
            $count = Transaction::whereDate('created_at', $date)
                ->select('kode_transaksi')
                ->distinct()
                ->count('kode_transaksi');

            $jumlah[] = $count;
        }

        return response()->json([
            'customers' => $dates_sorted,
            'jumlah' => $jumlah
        ]);
    }
}


    // Update Market
    public function updateMarket(Request $req)
    {
        $market = Market::first();
        $market->nama_toko = $req->nama_toko;
        $market->no_telp = $req->no_telp;
        $market->alamat = $req->alamat;
        $market->save();

        Session::flash('update_success', 'Pengaturan berhasil diubah');

        return back();
    }
}