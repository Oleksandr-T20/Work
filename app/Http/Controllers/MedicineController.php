<?php

namespace App\Http\Controllers;

use App\Models\Medicine;

class MedicineController extends Controller
{
    public function instructions(int $id)
    {
        $medicine = Medicine::findOrFail($id);

        return view('medicine.instructions', compact('medicine'));
    }
}
