<?php

namespace App\Http\Controllers;

use App\Models\Make;
use App\Models\Piece;
use App\Models\Product;
use App\Services\DeleteServiceWarehouse;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function createProduct(Request $request){
        try{
            $validateData = $request->validate([
                'name' => 'required|string|max:255'
            ]);
        }catch (ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        $product = Product::create([
            'name' => $validateData['name']
        ]);

        $response = [
            'product' => $product
        ];

        return Response($response, 201);
    }

    public function getProducts(){
        return Product::select('id', 'name', 'archive')->where('archive', false)->get();
    }

    public function getProduct($id)
    {
        return Product::select('id','name', 'archive')->where('archive', false)->where('id', $id)->get();
    }

    public function deleteProduct($id){
        $product = Product::find($id);
        $service = new DeleteServiceWarehouse();

        if($product && !$product->archive){
            $product->archive = true;
            $product->save();

            $pieces = Piece::where('id_product', $id)->where('archive', false)->get();
            $response = [ 'message'=>'Deleted !'];

            if(!$pieces->isEmpty()){
                foreach($pieces as $piece)
                    $service->deletePieceService($piece->id);
                $response[] = ['notice' => 'The pieces linked to the product have been archived.'];
            }

            $makes = Make::where('id_product', $id)->where('archive', false)->get();
            if(!$makes->isEmpty()){
                foreach($makes as $make)
                    $service->deleteMakesService($make->id, 'id_product');
            }
            $status = 200;
         }else{
            $response = [
                'message'=>'Your element doesn\'t exists'
            ];
            $status = 404;
        }

        return Response($response, $status);
    }

    public function updateProduct($id, Request $request){
        $product = Product::find($id);

        if($product && !$product->archive){
            try{
                $requestData = $request->validate([
                    'name' => 'string|max:255',
                ]);
            }catch(ValidationException $e){
                return response()->json(['errors' => $e->errors()], 422);
            }

            foreach($requestData as $key => $value){
                if(in_array($key, $product->getFillable()))
                    $product->$key = $value;
            }
            $product->save();

            $response = [
                'product' => $product
            ];
            $status = 200;
        }else{
            $response = [ 'message'=>'Your element doesn\'t exists' ];
            $status = 404;
        }

        return Response($response, $status);
    }
}
