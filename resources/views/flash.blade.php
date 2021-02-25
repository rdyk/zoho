@if(session()->has('status_success'))
    <div class="alert alert-success" role="alert">
        {{  session()->get('status_success')  }}
    </div>
@endif
@if(session()->has('status_error'))
    <div class="alert alert-danger" role="alert">
        {{  session()->get('status_error')  }}
    </div>
@endif