<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PenulisController extends Controller
{
    public function index()
    {
        $penulis = DB::table('penulis')->paginate(5);

        return view('backend.penulis.index', compact('penulis'));
    }


    public function create()
    {
        return view('backend.penulis.create');
    }
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            // Create a new user
            $user = User::insertGetId([
                'name' => $request->nama,
                'username' => $request->username,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'image' => $request->image, // Make sure the image storage is configured properly
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create a new penulis related to the user
            $penulis = DB::table('penulis')->insertGetId([
                'nama' => $request->nama,
                'alamat' => $request->alamat,
                'tanggal_lahir' => $request->tanggal_lahir,
                'telphone' => $request->telphone,
                'user_id' => $user, // Assign the user_id
                'created_by' => Auth::user()->id,
                'updated_by' => Auth::user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Save the details of the penulis related to the newly created penulis
            DB::table('detail_penulis')->insert([
                'domsili' => $request->domsili,
                'agama' => $request->agama,
                'email' => $request->email,
                'facebook' => $request->facebook,
                'instagram' => $request->instagram,
                'twtter' => $request->twtter,
                'linked_in' => $request->linked_in,
                'blog' => $request->blog,
                'youtube' => $request->youtube,
                'id_penulis' => $penulis,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('backend.penulis')->with('message', 'Data Penulis Berhasil Disimpan');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->route('backend.penulis')->with('error', 'Gagal menyimpan data: ' . $e->getMessage())->withErrors($e->getMessage());
        }
    }
    public function edit($id)
    {
        $datapenulis = DB::table('penulis')
            ->select('penulis.*', 'detail_penulis.domsili', 'detail_penulis.agama', 'detail_penulis.email', 'detail_penulis.facebook', 'detail_penulis.instagram', 'detail_penulis.twtter', 'detail_penulis.linked_in', 'detail_penulis.blog', 'detail_penulis.youtube')
            ->join('detail_penulis', 'penulis.id', '=', 'detail_penulis.id_penulis')
            ->where('penulis.id', $id)
            ->first();

        if (!$datapenulis) {
            return redirect()->route('backend.penulis')->with('error', 'Data Penulis tidak ditemukan');
        }

        return view('backend.penulis.edit', compact('datapenulis'));
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $affectedRows = DB::table('penulis')
                ->where('id', $id)
                ->update([
                    'nama' => $request->nama,
                    'alamat' => $request->alamat,
                    'tanggal_lahir' => $request->tanggal_lahir,
                    'telphone' => $request->telphone,
                    'user_id' => $request->id_user,
                    'created_by' => Auth::user()->id,
                    'updated_by' => Auth::user()->id,
                    'created_at' =>  \Carbon\Carbon::now(),
                    'updated_at' =>  \Carbon\Carbon::now(),
                ]);

            if ($affectedRows === 0) {
                throw new \Exception('Penulis tidak ditemukan');
            }

            $detailPenulis = DB::table('detail_penulis')
                ->where('id_penulis', $id)
                ->first();

            if ($detailPenulis) {
                DB::table('detail_penulis')
                    ->where('id_penulis', $id)
                    ->update([
                        'domsili' => $request->domsili,
                        'agama' => $request->agama,
                        'email' => $request->email,
                        'facebook' => $request->facebook,
                        'instagram' => $request->instagram,
                        'twtter' => $request->twtter,
                        'linked_in' => $request->linked_in,
                        'blog' => $request->blog,
                        'youtube' => $request->youtube,
                        'created_at' =>  \Carbon\Carbon::now(),
                        'updated_at' =>  \Carbon\Carbon::now(),
                    ]);
            }

            DB::commit();

            return redirect()->route('backend.penulis')->with('message', 'Data Penulis Berhasil Diperbarui');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->route('backend.edit_penulis', $id)->with('error', 'Gagal memperbarui data: ' . $e->getMessage())->withErrors($e->getMessage());
        }
    }

    public function show($id)
    {

        // Retrieve the details of the specified penulis and include the user's image
        $detailPenulis = DB::table('penulis')
            ->select(
                'penulis.*',
                'detail_penulis.domsili',
                'detail_penulis.agama',
                'detail_penulis.email',
                'detail_penulis.facebook',
                'detail_penulis.instagram',
                'detail_penulis.twtter',
                'detail_penulis.linked_in',
                'detail_penulis.blog',
                'detail_penulis.youtube',
                'users.image as gambar' // Include the user's image
            )
            ->join('detail_penulis', 'penulis.id', '=', 'detail_penulis.id_penulis')
            ->join('users', 'users.id', 'penulis.user_id') // Adjust this to your actual relationship between penulis and users
            ->where('penulis.id', $id)
            ->first();

        // Check if the detailPenulis data is found
        if ($detailPenulis) {
            return view('backend.penulis.show', compact('detailPenulis'));
        } else {
            return redirect()->route('backend.penulis')->with('error', 'Data Penulis tidak ditemukan');
        }
    }


    public function destroy($id)
    {
        DB::table('penulis')->where('id', $id)->delete();

        return redirect()->route('backend.penulis')->with('message', 'Penulis Berhasil Dihapus');
    }
}
