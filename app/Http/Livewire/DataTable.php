<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\distribuidores;
use DB;

class DataTable extends Component
{
    // public $statusFilter = null;
    public $status, $distribuidores;
    public $distribuidor =[],$stat=[];

//     public function mount(){
//         $distribuidores = distribuidores::all();
//         $this->distribuidor = $distribuidores;
//         $this->status = collect();
//     }

//     public function updateddistribuidores(){
        
//         $this->stat = distribuidores::where('status',1);
//         // $this->status = $this->stat->first()->id ?? null;
        
//         // $this->capitales = $this->capital->first()->id ?? null;
// }

     public function render()
    {
        // $id = 23;
        // $distibuidores = DB::table('tbldistribuidores');        
        // $varDis =   $distibuidores;

        // $distribuidores = distribuidores::query()
        // ->when($this->statusFilter, function($query){
        //     $query->where('status', $this->statusFilter);
        // });
        // echo $distribuidores;
   

        // return view('livewire.data-table');
        // return view('livewire.data-table',compact('distribuidores'));
        return view('livewire.data-table',['distribuidores'=> distribuidores::all()]);
        
    }
   
}
