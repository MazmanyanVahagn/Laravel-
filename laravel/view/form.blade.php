@extends('layouts.user')

@section('content')
    <div class="page-content wallets_form" data-module="wallets" data-block="wallets">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 mb-2">
                    @include('Bank::menu.index')
                </div>
            </div>

            <div class="row">
                <div class="col-12">

                    <form action="{{route('bank.wallets.save')}}" method="POST" id="bank_wallets_form" data-block="basic-form" class="needs-validation">

                        @include ('Bank::wallets.info')

                        <div class="info-block">
                            <span class="text-left text-bold">
                                {{tr('reflink')}}
                            </span>

                            <p class="center mt-1 text-bold">
                                @if ($referralLink)
                                    {{route('bank.register.ref', ['rkey' => $referralLink])}}
                                @else
                                    {{tr('required_wallets')}}
                                @endif
                            </p>
                            <p class="center mt-3 text-danger">
                                {{tr('wallets_info2')}}
                            </p>
                        </div>

                        {{csrf_field()}}

                        @foreach (\Bank\Models\MySQL\BankWallet::$types as $name => $key)
                            <div class="form-group">
                                <input type="text" class="form-control" id="{{$key}}" name="{{$key}}" value="{{$wallets->{$key} ?? ''}}">
                                <label for="{{$key}}">{{$name}}@if(isset(\Bank\Models\MySQL\BankWallet::$requiredTypes[$name])) <span class="text-danger">({{tr('required_for_referral_link')}})</span> @endif</label>
                            </div>
                        @endforeach

                        <button id="wallets_save" type="submit" class="button button-blue mt-3 mb-5">
                            {{tr('save')}}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection