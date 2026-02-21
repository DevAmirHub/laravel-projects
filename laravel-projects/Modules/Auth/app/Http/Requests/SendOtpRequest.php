<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Enums\VerificationActionType;
use Illuminate\Validation\Rule;
use Modules\Auth\Enums\ContactType;
use Illuminate\Validation\Validator;
use Modules\Auth\Services\VerificationCodeService;

class SendOtpRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'action' => [
                'bail',
                'required',
                'string',
                Rule::enum(VerificationActionType::class),
            ],
            'contact' => [
                'bail',
                'required',
                ...$this->getContactValidationRule(),
            ],
        ];
    }

    public function after() : array
    {
        return [
            function( Validator $validator )
            {
                if( $validator->errors()->isNotEmpty() ) {
                    return;
                }

                $contact = $this->input('contact');
                $contactType = ContactType::detectContactType($contact);
                $action = VerificationActionType::tryFrom($this->input('action'));

                if( !$action ) {
                    return;
                }

                $retryTime = ( new VerificationCodeService )->getRetryTime($contact, $contactType, $action);

                if( $retryTime > 0 ) {
                    if( $retryTime >= 1 ) {
                        $minutes = (int)ceil($retryTime);
                        $message = 'You have to wait ' . $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' before you can request another OTP.';
                    } else {
                        $retrySeconds = (int)ceil($retryTime * 60);
                        $message = 'You have to wait ' . $retrySeconds . ' second' . ($retrySeconds > 1 ? 's' : '') . ' before you can request another OTP.';
                    }
                    $validator->errors()->add('contact', $message);
                }
            }
        ];
    }

    public function getContactType(): array
    {
        $contactType = ContactType::detectContactType($this->input('contact' , default: ''));
        $verifyTypeAction = VerificationActionType::tryFrom($this->input('action'));
        
        if( !$verifyTypeAction ) {
            throw new \InvalidArgumentException('Invalid action');
        }

        if($contactType === ContactType::EMAIL) {
            return [ContactType::EMAIL, $this->input('contact')];
        } else {
            return [ContactType::PHONE, $this->input('contact'), 'phone' => $this->input('contact')];
        }
    }

    public function getContactValidationRule(): array
    {
        $contact = $this->input('contact', '');
        $contactType = ContactType::detectContactType($contact);
        $action = VerificationActionType::tryFrom($this->input('action'));

        if ($contactType === ContactType::EMAIL) {
            return [
                'email:rfc,dns',
                Rule::when($action && $action->isContactNeedToBeUnique(), rules: [
                    'unique:users,email',
                ]),
                Rule::when($action && $action->isContactNeedToBeExists(), rules: [
                    'exists:users,email',
                ]),
            ];
        }

        return [
            'phone:mobile',
            Rule::when($action && $action->isContactNeedToBeUnique(), rules: [
                'unique:users,phone',
            ]),
            Rule::when($action && $action->isContactNeedToBeExists(), rules: [
                'exists:users,phone',
            ]),
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        $contact = $this->input('contact', '');
        $contactType = ContactType::detectContactType($contact);

        if ($contactType === ContactType::EMAIL) {
            return [
                'contact.required' => 'The email address is required.',
                'contact.email' => 'Please provide a valid email address.',
                'contact.unique' => 'This email address is already registered.',
                'contact.exists' => 'This email address is not registered.',
            ];
        }

        return [
            'contact.required' => 'The phone number is required.',
            'contact.phone' => 'Please provide a valid phone number.',
            'contact.unique' => 'This phone number is already registered.',
            'contact.exists' => 'This phone number is not registered.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'action' => 'action type',
            'contact' => 'contact',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
