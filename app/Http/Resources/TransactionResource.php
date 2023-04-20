<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'date' =>  $this->date,
            'content' =>  $this->content,
            'amount' =>  $this->amount,
            'person' =>  $this->person,
            'transactionType' =>  $this->transaction_type,
            'categoryType' =>  $this->category_type,
        ];
    }
}
