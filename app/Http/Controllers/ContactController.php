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
            'message' => 'required'
        ]);

        $email = $request->all()['email'];
        $emailArray = $request->only(['name', 'email', 'phone', 'subject', 'message']);
        $contact = Contact::create($request->all());

        Mail::to($email)->send(new ContactMail($emailArray));
        Mail::to('pdts.portugal@gmail.com')->send(new ContactMailAdmin($emailArray));
        session()->flash('success', 'Message sent successfully.');

        return redirect()->back();
        // );
        //  Contact::create($request->all());

        //  return redirect()->back()
        //     ->with(['success' => 'Thank you for contacting us. we will contact you shortly.']);

    }
}
