<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use PDF;
use Auth;
use Carbon\Carbon;
use App\User;
use App\Acces;
use App\Market;
use App\Supply;
use App\Transaction;
use Illuminate\Http\Request;

class ReportManageController extends Controller
{
    // Show View Report Transaction
    public function reportTransaction()
    {
        $id_account = Auth::id();
        $check_access = Acces::where('user', $id_account)
        ->first();
        if($check_access->kelola_laporan == 1){
        	$transactions = Transaction::all();
            $array = array();
            foreach ($transactions as $no => $transaction) {
                array_push($array, $transactions[$no]->created_at->toDateString());
            }
            $dates = array_unique($array);
            rsort($dates);

            $arr_ammount = count($dates);
            $incomes_data = array();
            if($arr_ammount > 7){
            	for ($i = 0; $i < 7; $i++) { 
            		array_push($incomes_data, $dates[$i]);	
            	}
            }elseif($arr_ammount > 0){
            	for ($i = 0; $i < $arr_ammount; $i++) { 
            		array_push($incomes_data, $dates[$i]);
            	}
            }
            $incomes = array_reverse($incomes_data);

        	$startOfWeek = now()->startOfWeek()->format('Y-m-d 00:00:00');
            $endOfWeek = now()->endOfWeek()->format('Y-m-d 23:59:59');

            $top_products_week = Transaction::select('nama_barang', \DB::raw('SUM(jumlah) as total_terjual'))
                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->groupBy('nama_barang')
                ->orderByDesc('total_terjual')
                ->limit(3)
                ->get();

            return view('report.report_transaction', compact('dates', 'incomes', 'top_products_week'));
        }else{
            return back();
        }
    }

    // Filter Report Transaction
    public function filterTransaction(Request $req)
    {
        $id_account = Auth::id();
        $check_access = Acces::where('user', $id_account)
        ->first();
        if($check_access->kelola_laporan == 1){
        	$start_date = $req->tgl_awal;
        	$end_date = $req->tgl_akhir;
        	$start_date2 = $start_date[6].$start_date[7].$start_date[8].$start_date[9].'-'.$start_date[3].$start_date[4].'-'.$start_date[0].$start_date[1].' 00:00:00';
        	$end_date2 = $end_date[6].$end_date[7].$end_date[8].$end_date[9].'-'.$end_date[3].$end_date[4].'-'.$end_date[0].$end_date[1].' 23:59:59';
        	$supplies = Transaction::select()
        	->whereBetween('created_at', array($start_date2, $end_date2))
        	->get();
            $array = array();
            foreach ($supplies as $no => $supply) {
                array_push($array, $supplies[$no]->created_at->toDateString());
            }
            $dates = array_unique($array);
            rsort($dates);

        	return view('report.report_transaction_filter', compact('dates'));
        }else{
            return back();
        }
    }

    // Filter Chart Transaction
    public function chartTransaction($id)
    {
        $id_account = Auth::id();
        $check_access = Acces::where('user', $id_account)->first();

        if ($check_access->kelola_laporan != 1) {
            return back();
        }

        // Ambil semua transaksi unik berdasarkan kode_transaksi
        $supplies = Transaction::selectRaw('MIN(id) as id')
            ->groupBy('kode_transaksi')
            ->pluck('id');

        // Ambil tanggal-tanggal dari transaksi unik
        $transactions = Transaction::whereIn('id', $supplies)->get();
        $array = [];

        foreach ($transactions as $transaction) {
            $array[] = $transaction->created_at->toDateString();
        }

        $dates = array_values(array_unique($array));
        rsort($dates);

        // Ambil jumlah data sesuai periode
        $limit = 0;
        if ($id === 'minggu') {
            $limit = 7;
        } elseif ($id === 'bulan') {
            $limit = 30;
        } elseif ($id === 'tahun') {
            $limit = 365;
        }

        $incomes_data = array_slice($dates, 0, $limit);
        $incomes = array_reverse($incomes_data);

        // Hitung total berdasarkan transaksi unik per hari
        $total = [];
        foreach ($incomes as $income) {
            $unique_ids = Transaction::selectRaw('MIN(id) as id')
                ->whereDate('created_at', $income)
                ->groupBy('kode_transaksi')
                ->pluck('id');

            $daily_total = Transaction::whereIn('id', $unique_ids)->sum('total');
            $total[] = $daily_total;
        }

        return response()->json([
            'incomes' => $incomes,
            'total' => $total
        ]);
    }

    // Export Transaction Report
    public function exportTransaction(Request $req)
    {
        set_time_limit(300);

        $id_account = Auth::id();
        $check_access = Acces::where('user', $id_account)->first();

        if ($check_access->kelola_laporan == 1) {
            $jenis_laporan = $req->jns_laporan;

            if ($jenis_laporan == 'period') {
                if ($req->period == 'minggu') {
                    $start = Carbon::now()->subWeeks($req->time)->startOfDay();
                    $end = Carbon::now()->endOfDay();
                } elseif ($req->period == 'bulan') {
                    $start = Carbon::now()->startOfMonth()->startOfDay();
                    $end = Carbon::now()->endOfMonth()->endOfDay();
                } elseif ($req->period == 'tahun') {
                    $start = Carbon::now()->subYears($req->time)->startOfDay();
                    $end = Carbon::now()->endOfDay();
                }
            } else {
                $start = Carbon::createFromFormat('d/m/Y', $req->tgl_awal_export)->startOfDay();
                $end = Carbon::createFromFormat('d/m/Y', $req->tgl_akhir_export)->endOfDay();
            }

            // Ambil id terkecil per kode_transaksi agar hanya 1 baris tiap transaksi
            $unique_ids = Transaction::selectRaw('MIN(id) as id')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('kode_transaksi')
                ->pluck('id');

            $transactions = Transaction::whereIn('id', $unique_ids)->get();

            // Ambil tanggal-tanggal unik
            $dates = $transactions->pluck('created_at')->map(function ($date) {
                return Carbon::parse($date)->toDateString();
            })->unique()->sortDesc()->values()->toArray();

            $tgl_awal = $start;
            $tgl_akhir = $end;
            $market = Market::first();

            // Kirim juga $transactions ke PDF
            $pdf = PDF::loadview('report.export_report_transaction', compact('dates', 'tgl_awal', 'tgl_akhir', 'market', 'transactions'));
            return $pdf->stream();
        } else {
            return back();
        }
    }

    public function reportRecap()
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Ambil data transaksi per barang dari tabel `transactions`
        $transactions = DB::table('transactions')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->select(
                'created_at as tanggal',
                'nama_barang as keterangan',
                DB::raw('(jumlah * harga) as masuk'), // ← asumsi kolom jumlah & harga ada
                DB::raw('NULL as keluar')
            )
            ->get();

        $pengeluaran = DB::table('supplies')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->select(
                'created_at as tanggal',
                'nama_barang as keterangan',
                DB::raw('NULL as masuk'),
                DB::raw('(jumlah * harga_beli) as keluar')
            )
            ->get();

        $merged = $transactions->merge($pengeluaran)->sortBy('tanggal')->values();

        $saldo = 0;
        $rekap = $merged->map(function ($item) use (&$saldo) {
            $masuk = $item->masuk ?? 0;
            $keluar = $item->keluar ?? 0;
            $saldo += $masuk - $keluar;
            return (object) [
                'tanggal'    => $item->tanggal,
                'keterangan' => $item->keterangan,
                'masuk'      => $masuk,
                'keluar'     => $keluar,
                'saldo'      => $saldo,
            ];
        });

        $total_pemasukan = $transactions->sum('masuk');
        $total_pengeluaran = $pengeluaran->sum('keluar');
        $selisih = $total_pemasukan - $total_pengeluaran;

        return view('report.recap', compact('rekap', 'total_pemasukan', 'total_pengeluaran', 'selisih'));
    }


    public function exportRekap(Request $request)
    {
        $bulan = $request->bulan;
        $startOfMonth = Carbon::createFromFormat('Y-m', $bulan)->startOfMonth();
        $endOfMonth = Carbon::createFromFormat('Y-m', $bulan)->endOfMonth();

        // Ambil data transaksi dari tabel transactions
        $transactions = DB::table('transactions')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->select(
                'created_at as tanggal',
                'nama_barang as keterangan',
                DB::raw('(jumlah * harga) as masuk'),
                DB::raw('NULL as keluar')
            )
            ->get();

        // Ambil data pengeluaran dari tabel supplies
        $pengeluaran = DB::table('supplies')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->select(
                'created_at as tanggal',
                'nama_barang as keterangan',
                DB::raw('NULL as masuk'),
                DB::raw('(jumlah * harga_beli) as keluar')
            )
            ->get();

        // Gabung & urutkan data
        $merged = $transactions->merge($pengeluaran)->sortBy('tanggal')->values();

        // Proses saldo berjalan
        $saldo = 0;
        $rekap = $merged->map(function ($item) use (&$saldo) {
            $masuk = $item->masuk ?? 0;
            $keluar = $item->keluar ?? 0;
            $saldo += $masuk - $keluar;
            return (object) [
                'tanggal'    => $item->tanggal,
                'keterangan' => $item->keterangan,
                'masuk'      => $masuk,
                'keluar'     => $keluar,
                'saldo'      => $saldo,
            ];
        });

        // Hitung total
        $total_pemasukan = $transactions->sum('masuk');
        $total_pengeluaran = $pengeluaran->sum('keluar');
        $selisih = $total_pemasukan - $total_pengeluaran;

        // Export PDF
        $market = Market::first();
        $pdf = PDF::loadView('report.export_rekap', compact('rekap', 'total_pemasukan', 'total_pengeluaran', 'selisih', 'bulan', 'market'));
        return $pdf->stream('Laporan-Rekap-' . $bulan . '.pdf');
    }
}