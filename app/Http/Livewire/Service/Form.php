<?php

namespace App\Http\Livewire\Service;

use App\Models\CategoryService;
use App\Models\Client;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{

    use WithFileUploads;
    
    //User actual
    public $user;
    
    public $method;
    public $service;
    public $serviceAgreementTmp;
    public $userArray = [];

    public $searchUser;
    protected $queryString = ['searchUser' => ['except' => '']];

    protected $listeners = ['render'];

    public function mount(Service $service, $method){
        $this->service = $service;
        $this->method = $method;
        foreach($this->service->users as $user){
            array_push($this->userArray, "".$user->id."");
        }
    }

    protected function rules()
    {
        return [
            'service.type' => 'required',
            'service.category_service_id' => 'required',
            'service.client_id' => 'required',
            'service.name' => 'required',
            'service.start_date' => 'required',
            'service.due_date' => 'nullable',
            'service.due_day' => 'nullable',
            'service.price' => 'required',
            'service.note' => 'nullable',
            'service.has_invoice' => 'nullable',
            'service.finished' => 'nullable',
        ];
    }

    public function render()
    {
        $users = User::orderBy('name');
        $categoryServices = CategoryService::orderBy('id', 'desc')->cursor();
        $clients = Client::orderBy('id', 'desc')->cursor();

        if($this->searchUser){
            $users = $users->where('name', 'LIKE', "%{$this->searchUser}%");
        }

        $users = $users->cursor();

        $this->emit('renderJs');
        return view('livewire.service.form', compact('users', 'categoryServices', 'clients'));
    }

    public function store(){
        $this->validate();
        $this->save();
        $this->saveServiceAgreement();
        $this->service->save();
        $this->saveUsers();
        session()->flash('alert','Servicio agregado con exito');
        session()->flash('alert-type', 'success');
        return redirect()->route('service.index');
    }

    public function update(){
        $this->validate();
        $this->save();
        $this->saveServiceAgreement();
        $this->service->update();
        $this->saveUsers();
        session()->flash('alert','Servicio actualizado con exito');
        session()->flash('alert-type', 'success');
        return redirect()->route('service.index');
    }

    public function save(){
        if($this->service->type == 'Proyecto'){
            $this->validate([
                'service.due_date' => 'required',
            ]);
        }elseif($this->service->type == 'Mensual'){
            $this->validate([
                'service.due_day' => 'required',
            ]);
        }
    }

    public function saveServiceAgreement(){
        if($this->serviceAgreementTmp){
            if(Storage::exists($this->service->service_agreement)){
                Storage::delete($this->service->service_agreement);
            }

            $path = $this->serviceAgreementTmp->store('public/service');
            $this->service->service_agreement = $path;
        }
    }

    public function saveUsers(){
        $this->service->users()->sync($this->userArray);
    }

    public function removeServiceAgreement(){
        if($this->service->service_agreement){
            if(Storage::exists($this->service->service_agreement)){
                Storage::delete($this->service->service_agreement);
            }
            
            $this->service->service_agreement = null;
            $this->service->update();
        }
        $this->reset('serviceAgreementTmp');
        $this->alert('success', 'Acuerdo de servicio eliminado con exito');
    }

}
