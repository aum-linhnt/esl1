<?php

namespace App\Integrations\AiTutor;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use TDSoft\AiTutor\Contracts\CreditAdministrator;
use TDSoft\AiTutor\Core\AiException;

final class WebsiteCreditAdministrator implements CreditAdministrator
{
    public function actorId(): ?string
    {
        $user = Auth::user();

        return $user && $user->isAdmin() && $user->isActive() ? (string) $user->getAuthIdentifier() : null;
    }

    private function authorize(): void
    {
        if ($this->actorId() === null) {
            throw new AiException('AI_CREDIT_ADMIN_FORBIDDEN');
        }
    }

    public function recipients(string $search): array
    {
        $this->authorize();
        $query = User::query()->where('status', 'active');
        if ($search === '') {
            $query->whereKey($this->actorId());
        } else {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('id', $search));
        }

        return $query->orderBy('id')->limit(20)->get(['id', 'name'])
            ->map(fn ($user) => ['id' => (string) $user->id, 'name' => $user->name])->all();
    }

    public function recipient(string $id): array
    {
        $this->authorize();
        $user = User::find($id);
        if (! $user || ! $user->isActive()) {
            throw new AiException('AI_CREDIT_RECIPIENT_INVALID');
        }

        return ['id' => (string) $user->id, 'name' => $user->name];
    }
}
