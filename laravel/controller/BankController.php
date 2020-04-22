<?php
/**
 * Created by PhpStorm.
 * User: NUM-11
 * Date: 06/09/2018
 * Time: 13:56
 */

namespace Bank\Http\Controllers;

use App\GlobalLog;
use App\Helpers\Helper;
use App\OutOrder;
use App\RegistrationSmsVerification;
use App\TransactionErrorLog;
use App\VerificationChance;
use Bank\Exceptions\BankSecurityException;
use Bank\Exceptions\CoinPaymentException;
use Bank\Exceptions\EmptyDepositException;
use Bank\Exceptions\NotEnoughEarnedViuException;
use Bank\Exceptions\PerfectMoneyException;
use Bank\Helpers\DepositeHelper;
use Bank\Helpers\ReferralHelper;
use Bank\Helpers\SecurityHelper;
use Bank\Helpers\TransactionHelper;
use Bank\Models\MySQL\BankCoinPayment;
use Bank\Models\MySQL\BankCryptoRate;
use Bank\Models\MySQL\BankDeposite;
use Bank\Models\MySQL\BankDepositePlan;
use Bank\Models\MySQL\BankOutTransaction;
use Bank\Models\MySQL\BankPayeer;
use Bank\Models\MySQL\BankPerfectMoney;
use Bank\Models\MySQL\BankReferralTransaction;
use Bank\Models\MySQL\BankTransaction;
use Bank\Models\MySQL\BankTransactionsType;
use Bank\Models\MySQL\BankWallet;
use Core\Globals\Helpers\CodeGenerator;
use Core\Globals\Repositories\SMSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Bank Module front controller
 *
 * @package Bank\Http\Controllers
 */
class BankController extends Controller
{
    /**
     * Bank desposites info etc...
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        $deposits = BankDeposite::where('user_id', Auth::user()->id)->whereIn('status', [
            BankDeposite::STATUS_FINISHED,
            BankDeposite::STATUS_STARTED
        ])->orderBy('created_at', 'DESC')->get();

        $referralLink = Auth::user()->getBankReferralLink();
        $referralName = Auth::user()->getBankReferralName();

        return view('Bank::dashboard.index')->with([
            'referralLink' => $referralLink,
            'referralName' => $referralName,
            'deposites' => $deposits
        ]);
    }

    /**
     * Wallets form (create update)
     *
     * @return \Illuminate\View\View
     */
    public function wallets()
    {
        $wallets = Auth::user()->getBankWallets();
        $referralLink = Auth::user()->getBankReferralLink();

        return view('Bank::wallets.form')->with([
            'wallets' => $wallets,
            'referralLink' => $referralLink
        ]);
    }

    /**
     * Save wallets info
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveWallets(Request $request)
    {
        try {
            BankWallet::validateSave($request);
            return back()->with('flash_success', tr('wallets_saved'));
        } catch(BadRequestHttpException $e) {
            return back()->with('flash_error', $e->getMessage());
        } catch(\Exception $e) {
            return back()->with('flash_error', tr('something_error'));
        }
    }

    /**
     * Withdraw earned viu from deposit
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function percentWithdrawForm(Request $request)
    {
        $depositModel = BankDeposite::where('user_id', Auth::user()->id)
            ->where('id', $request->id)
            ->first();

        if (!$depositModel) {
            abort(404);
        }
        if ($depositModel->earned_viu == 0) {
            return back()->with('flash_error', tr('deposit_has_not_earned_viu'));
        }

        return view('Bank::withdraw.percents_withdraw')->with([
            'deposit' => $depositModel
        ]);
    }

    /**
     * Deposit Earned viu withdrawal
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function percentWithdraw(Request $request)
    {
        try {
            $captcha = Helper::validateCaptcha($request);
            if (!isset($captcha['success']) || $captcha['success'] == false) {
                throw new BadRequestHttpException(tr('are_you_robot'));
            }

            SecurityHelper::canWithdraw('percent', $request->id);

            $result = TransactionHelper::createPercentWithdrawal($request);
            if (!$result) {
                throw new \Exception();
            }
            return redirect(route('bank.dashboard'))->with('flash_success', tr('verify_your_order'));
        } catch (BankSecurityException $e) {
            return redirect(route('bank.dashboard'))->with('flash_error', $e->getMessage());
        } catch (BadRequestHttpException $e) {
            return back()->with('flash_error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('flash_error', tr('something_error'));
        }
    }

    /**
     * Landing page
     *
     * @param Request $request
     * @return $this
     */
    public function landing(Request $request)
    {
        if ($request->rKey) {
            $cookie = cookie('bank_referral_key', $request->rkey, 259200);
            return redirect(route('bank.landing'))->withCookie($cookie);
        }
        $plans = BankDepositePlan::orderBy('sum', 'ASC')->get();

        return view('Bank::landing.index')->with([
            'plans' => $plans
        ]);
    }

    /**
     * Transactions history
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function transactionsHistory(Request $request)
    {
        $transactions = TransactionHelper::search($request);

        if ($request->ajax()) {
            $html = View::make('Bank::transactions.transaction_items')->with([
                'transactions' => $transactions
            ])->render();

            return response()->json([
                'view' => $html,
                'success' => true
            ]);
        }

        $filters = BankTransactionsType::all();

        return view('Bank::transactions.index')->with([
            'transactions' => $transactions,
            'filters' => $filters
        ]);
    }
}