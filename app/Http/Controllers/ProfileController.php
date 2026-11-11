<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UserNameUpdateRequest;
use App\Identity\Runtime\UserNameService;
use App\Identity\Runtime\CurrentIdentityResolver;
use App\Identity\CanonicalIdentityReadException;
use App\Security\JmbEncryptedReadException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Identity\CanonicalIdentityWriteException;
use App\Identity\Runtime\CanonicalHttpIdentityService;
use App\Identity\Runtime\IdentityMutationDeniedException;
use App\Identity\Runtime\IdentityUseGateException;
use App\Support\PhoneNumber;
use App\Security\JmbDualWrite;
use App\Support\UserType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function editName(Request $request): View
    {
        $user = $request->user();
        return view('profile.name', [
            'user' => $user,
            'subjectIdentity' => app(CurrentIdentityResolver::class)->viewFor($user),
        ]);
    }

    public function updateName(UserNameUpdateRequest $request): RedirectResponse
    {
        try {
            app(UserNameService::class)->update($request->user(), $request->validated('first_name'), $request->validated('last_name'));
        } catch (IdentityMutationDeniedException|IdentityUseGateException|CanonicalIdentityReadException|CanonicalIdentityWriteException|JmbEncryptedReadException $e) {
            return back()->withErrors(['first_name' => 'Ime nije sačuvano. Identitet nije dostupan ili izmjena nije dozvoljena.'])->withInput();
        }

        return redirect()->route('profile.name.edit')->with('success', 'Ime i prezime su uspješno sačuvani.');
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $user = User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                return $this->updateAccount($request, $user);
            });
        } catch (IdentityMutationDeniedException $e) {
            abort(403, $e->getMessage());
        } catch (IdentityUseGateException|CanonicalIdentityReadException|JmbEncryptedReadException $e) {
            return back()->withErrors(['user_type' => 'Identitet nije dostupan za izmjenu.'])->withInput();
        } catch (CanonicalIdentityWriteException $e) {
            if (str_contains($e->getMessage(), 'PIB')) {
                return back()->withErrors(['pib' => $e->getMessage() === 'PIB je već registrovan.' ? $e->getMessage() : 'PIB nije ispravan.'])->withInput();
            }
            return back()->withErrors(['user_type' => 'Ažuriranje identiteta nije uspjelo.'])->withInput();
        }
    }

    private function updateAccount(ProfileUpdateRequest $request, User $user): RedirectResponse
    {
        $service = app(CanonicalHttpIdentityService::class);
        $user = app(UserNameService::class)->update($user, $request->first_name, $request->last_name);
        $service->updateProfileIdentity($user, $request);
        if ($service->usesCanonicalWrites()) {
            if ($user->email !== $request->email) {
                $user->email = strtolower($request->email);
                $user->email_verified_at = null;
                if (! $user->save()) {
                    throw new CanonicalIdentityWriteException('Account changes were not saved.');
                }
            }

            return redirect()->route('dashboard')->with('success', 'Profil je uspješno ažuriran.');
        }

        // Ažuriraj osnovne podatke
        $user->phone = PhoneNumber::normalize($request->phone);
        $user->address = $request->address;
        $user->city = $request->city;

        $incomingType = $request->input('user_type', $user->user_type);

        if ($user->collectsBusinessIdentity() && $request->has('user_type')) {
            $user->user_type = $request->user_type;
        }

        if (UserType::requiresResidentialStatus($incomingType) && $request->filled('residential_status')) {
            $user->residential_status = $request->residential_status;
        }

        if ($request->has('jmb')) {
            JmbDualWrite::assignLogical($user, 'jmb', $request->jmb ?: null);
        }

        if ($request->has('pib')) {
            $user->pib = $request->pib ?: null;
        }

        if ($request->has('company_name')) {
            $user->company_name = $request->company_name ? trim($request->company_name) : null;
        }

        // Ažuriraj broj pasoša (za nerezidente)
        if ($request->has('passport_number')) {
            $user->passport_number = $request->passport_number ? strtoupper($request->passport_number) : null;
        }

        // Ažuriraj email ako je promijenjen
        if ($user->email !== $request->email) {
            $user->email = strtolower($request->email);
            $user->email_verified_at = null;
        }

        if (! $user->save()) {
            throw new CanonicalIdentityWriteException('Account changes were not saved.');
        }

        return redirect()->route('dashboard')->with('success', 'Profil je uspješno ažuriran.');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('profile.edit')->with('success', 'Lozinka je uspješno promijenjena.');
    }
}
