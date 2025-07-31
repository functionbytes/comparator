<?php


use App\Models\Return\ReturnHistory;
use App\Models\Return\ReturnState;

trait HasReturnStatus
{
    public function isEditable(): bool
    {
        return in_array($this->status->state_id, [
            ReturnState::STATE_NEW,
            ReturnState::STATE_VERIFICATION
        ]);
    }

    public function canBeApproved(): bool
    {
        return $this->status->state_id === ReturnState::STATE_NEW;
    }

    public function canBeRefunded(): bool
    {
        return $this->status->state_id === ReturnState::STATE_RESOLVED &&
            !$this->is_refunded;
    }

    public function transitionTo($statusId, $notes = null)
    {
        DB::transaction(function() use ($statusId, $notes) {
            ReturnHistory::create([
                'id_return_request' => $this->id,
                'id_return_status' => $statusId,
                'description' => $notes,
                'id_employee' => auth()->id() ?? 0
            ]);

            // Actualizar estado
            $this->update(['status_id' => $statusId]);

            // Disparar eventos según el nuevo estado
            $this->handleStatusChange($statusId);
        });
    }
}
