<?php

namespace App\Http\Controllers;

use App\Jobs\SendMail;
use App\Models\Bank;
use App\Models\Company;
use Illuminate\Http\Request;
use App\Models\CompanyDocument;
use App\Models\RequestDocument;

class RequestedDocumentsController extends Controller
{
  public function upload(Bank $bank, $company_id)
  {
    $company = Company::find($company_id);

    if (!$company) {
      $company = Company::find(decrypt($company_id));
    }

    $documents = RequestDocument::where('company_id', $company->id)->where('status', 'pending')->get();

    $pageConfigs = ['myLayout' => 'blank'];

    return view('content.bank.companies.requested-documents-upload', compact('pageConfigs', 'documents', 'company', 'bank'));
  }

  public function store(Request $request, Bank $bank, Company $company)
  {
    $documents = [];

    foreach ($request->files as $files) {
      foreach ($files as $key => $file) {

        array_push($documents, $key);

        $document = CompanyDocument::where('company_id', $company->id)->where('name', $key)->first();

        if (!$document) {
          CompanyDocument::create([
            'company_id' => $company->id,
            'name' => $key,
            'path' => pathinfo($request->file('files')[$key]->store('documents', 'company'), PATHINFO_BASENAME),
          ]);
        } else {
          $document->update([
            'status' => 'pending',
            'path' => pathinfo($request->file('files')[$key]->store('documents', 'company'), PATHINFO_BASENAME),
          ]);
        }

        $document = RequestDocument::where('company_id', $company->id)->where('name', $key)->first();

        $document->update([
          'status' => 'uploaded'
        ]);
      }
    }

    foreach ($company->relationshipManagers as $user) {
      SendMail::dispatchAfterResponse($user->email, 'DocumentsUploaded', ['documents' => $documents, 'company_id' => $company->id]);
    }

    toastr()->success('', 'Documents successfully uploaded');

    return back();
  }
}
