<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use App\Traits\DatosimpleTraits;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Facades\Excel;


class importarnominafiscal implements FromCollection
{
    protected $id;
    function __construct($id) {
            $this->id = $id;
    }

    use DatosimpleTraits;
    use Exportable;
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $nominas=  $this->obtenerdepositosquincena($this->id);
        return $nominas;
    }
}
