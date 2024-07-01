<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Mail\ContactMail;
use App\Mail\ContactMailAdmin;
use App\Models\Contact;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Client;

class ContactController extends Controller
{
    /**
     * Write code on Method
     *
     * @return response()
     */
    public function index()
    {
        return view('contact');
    }

    /**
     * Write code on Method
     *
     * @return response()
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'nullable|numeric',
            'subject' => 'required',
            'message' => 'required',
            'g-recaptcha-response' => 'required'
        ]);

        // Verify reCAPTCHA
        $client = new Client();
        $response = $client->post('https://www.google.com/recaptcha/api/siteverify', [
            'form_params' => [
                'secret' => config('services.recaptcha.secret_key'),
                'response' => $request->input('g-recaptcha-response'),
            ]
        ]);

        $body = json_decode((string)$response->getBody());

        if (!$body->success) {
            return back()->withErrors(['captcha' => 'ReCAPTCHA verification failed. Please try again.'])->withInput();
        }

        // If reCAPTCHA is successful
        $email = $request->input('email');
        $emailArray = $request->only(['name', 'email', 'phone', 'subject', 'message']);
        $contact = Contact::create($request->all());

        Mail::to($email)->send(new ContactMail($emailArray));
        Mail::to('pdts.portugal@gmail.com')->send(new ContactMailAdmin($emailArray));
        session()->flash('success', 'Mensagem enviada com sucesso.');

        return redirect()->back();
    }
}
