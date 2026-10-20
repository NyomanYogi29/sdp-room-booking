<?php

namespace App\Http\Requests\Booking;

use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'exists:rooms,id'],
            'title' => ['required', 'string', 'max:255'],
            'purpose' => ['nullable', 'string', 'max:1000'],
            'attendees' => ['required', 'integer', 'min:1'],
            'start_at' => ['required', 'date', 'after:now'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'slot_start' => ['nullable', 'string', 'in:A,B,C,D,E,F,G,H,I,J,K,L,M,N,O'],
            'slot_end' => ['nullable', 'string', 'in:A,B,C,D,E,F,G,H,I,J,K,L,M,N,O'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $roomId = $this->input('room_id');
            $attendees = (int) $this->input('attendees');
            $startAt = $this->input('start_at');
            $endAt = $this->input('end_at');

            if ($roomId) {
                $room = Room::find($roomId);
                if ($room) {
                    if ($room->status !== 'active') {
                        $validator->errors()->add('room_id', 'Ruangan ini sedang dalam masa perbaikan/maintenance dan tidak dapat dipesan.');
                    }

                    if ($attendees > $room->capacity) {
                        $validator->errors()->add('attendees', "Jumlah peserta ({$attendees}) melebihi batas kapasitas maksimal ruangan ({$room->capacity}).");
                    }
                }
            }

            if ($startAt && $endAt) {
                $start = Carbon::parse($startAt);
                $end = Carbon::parse($endAt);

                // Validasi jam operasional (07:00 - 22:00)
                if ($start->hour < 7 || $end->hour > 22 || ($end->hour === 22 && $end->minute > 0)) {
                    $validator->errors()->add('start_at', 'Pemesanan hanya diizinkan dalam jam operasional kampus (07:00 - 22:00).');
                }
            }
        });
    }
}
