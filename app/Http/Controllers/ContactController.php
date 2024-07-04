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
            'captcha' => 'required|captcha',
        ]);


        $email = $request->input('email');
        $emailArray = $request->only(['name', 'email', 'phone', 'subject', 'message']);
        $contact = Contact::create($request->all());

        Mail::to($email)->send(new ContactMail($emailArray));
        Mail::to('pdts.portugal@gmail.com')->send(new ContactMailAdmin($emailArray));
        session()->flash('success', 'Mensagem enviada com sucesso.');

        return redirect()->back()->with('success', 'Mensagem enviada com sucesso');
    }
}
