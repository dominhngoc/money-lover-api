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
            'id' =>  $this->id,
            'date' =>  $this->date,
            'content' =>  $this->content,
            'amount' =>  $this->amount,
            'person' =>  $this->person,
            'transactionType' =>  $this->transaction_type,
            'categoryType' =>  $this->category_type,
            'isComingSoon' =>  $this->is_coming_soon,
            'isInstallment' =>  $this->is_installment,
            'total' =>  $this->total,
            'startDate' =>  $this->start_date,
            'numberOfMonths' =>  $this->number_of_months,
            'totalOfMonths' =>  $this->total_of_months,
            'paid' =>  $this->paid,
            'paidCount' =>  $this->paidCount,
            'remaining' =>  $this->remaining,
            'installmentId' =>  $this->installment_id,
            'updatedMonth' => $this->updated_at
        ];
    }
}
