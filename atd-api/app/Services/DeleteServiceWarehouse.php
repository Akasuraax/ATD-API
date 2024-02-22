<?php

namespace App\Services;

use App\Models\Make;
use App\Models\Piece;

class DeleteServiceWarehouse{
    public function deletePieceService($id){
        $piece = Piece::find($id);

        if($piece && !$piece->archive){
            $piece->archive = true;
            $piece->save();

            $response = [
                'message'=>'Deleted !'
            ];
            $status = 200;
        }else{
            $response = [
                'message'=>'Your element doesn\'t exists'
            ];
            $status = 404;
        }

        return Response($response, $status);
    }

    public function deleteMakesService($id, $element){
        $makes = Make::where($element, $id)->where('archive', false)->get();

        if($makes->isEmpty()){
            foreach($makes as $make){
                $make->archive = true;
                $make->save();
            }
            $response = [ 'message'=>'Deleted !' ];
            $status = 200;
        }else{
            $response = [ 'message'=>'Your element doesn\'t exists' ];
            $status = 404;
        }

        return Response($response, $status);
    }
}
