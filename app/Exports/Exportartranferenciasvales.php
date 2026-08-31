<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\prestamos_valesenc;

class Exportartranferenciasvales implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $tranferenciasvales=$this->exportartranfervales();

        foreach($tranferenciasvales as $lst)
        {
            $actualizarodp = prestamos_valesenc::find($lst->id);
            $actualizarodp->otrosconceptos1 = 1;
            $actualizarodp->save();
        }

        return $tranferenciasvales;
    }
}
