<?php

namespace App\Models;

use App\Jobs\ImportBlacklistJob;
use App\Library\AutoBillingData;
use App\Library\Facades\Billing;
use App\Library\Facades\SubscriptionFacade;
use App\Library\Traits\HasCache;
use App\Library\Traits\HasUid;
use App\Library\Traits\TrackJobs;
use App\Models\Automation\Automation;
use App\Models\Subscriber\Subscriber;
use App\Models\Subscription\Subscription;
use App\Models\Subscription\SubscriptionLog;
use App\Models\Template\Template;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens , LogsActivity , HasRoles , HasCache ,HasUid , TrackJobs;

    protected $table = 'users';

    protected $quotaTracker;

    // Plan status
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ACTIVE = 'active';
    public const BASE_DIR = 'app/customers'; // storage/customers/000000
    public const ATTACHMENTS_DIR = 'home/attachments';  // storage/customers/000000/home/files
    public const TEMPLATES_DIR = 'home/templates';  // storage/customers/000000/home/files
    public const PRODUCT_DIR = 'home/products';
    public const LOGS_DIR = 'home/logs/';

    protected static $recordEvents = ['deleted','updated','created'];

    protected $fillable = [
        'uid',
        'firstname',
        'lastname',
        'identification',
        'cellphone',
        'email',
        'password',
        'address',
        'available',
        'verified',
        'terms',
        'validation',
        'page',
        'setting',
        'role',
        'company',
        'detail',
        'user_img',
        'citie_id',
        'enterprise_id',
        'email_verified_at',
        'remember_token',
        'timezone',
        'voilated',
        'verified',
        'last_login_at',
        'last_login_ip',
        'last_logins_at',
        'created_at',
        'updated_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $dates = [
        'last_login_at',
        'deleted_at'
    ];

    protected $appends = ['full_name', 'image'];
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    protected $casts = [
        'active' => 'boolean',
        'confirmed' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {

        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logFillable()
            ->setDescriptionForEvent(fn(string $eventName) => "This model has been {$eventName}");

    }


    public function scopeAvailable($query)
    {
        return $query->where('users.available', 1);
    }

    public function redirect()
    {
        return redirect()->route($this->redirectRouteName());
    }

    public function route()
    {
        return route($this->redirectRouteName());
    }


    public function redirectRouteName()
    {
        return match (true) {
            $this->hasRole('manager') => 'manager.dashboard',
            $this->hasRole('inventaries') => 'inventarie.dashboard',
            $this->hasRole('shops') => 'shop.dashboard',
            $this->hasRole('callcenters') => 'callcenter.dashboard',
            $this->hasRole('supports') => 'support.dashboard',
            $this->hasRole('administratives') => 'administrative.dashboard',
            default => 'login',
        };
    }


    public function passwordHistories(): HasMany
    {
        return $this->hasMany('App\Models\Setting\PasswordHistory','user_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany('App\Models\Setting\Session','user_id');
    }


    public function orders(): HasMany
    {
        return $this->hasMany('App\Models\Order\Order','user_id');
    }


    public static function auth(){

        return Auth::user();
    }


    public function session() : HasOne
    {
        return $this->hasOne('App\Models\Setting\Session');
    }

    public function scopeValidationsEmail($query,$email)
    {
        return $query->where('email', $email)->get();
    }

    public function scopeValidationEmail($query,$email)
    {
        return $query->where('email', $email)->first();
    }

    public function scopeValidations($query )
    {
        return $query->where('uid', null)->get();
    }

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeUid($query ,$uid)
    {
        return $query->where('uid', $uid)->first();
    }

    public function scopeEmail($query ,$email)
    {
        return $query->where('email', $email)->first();
    }

    public static function existence($uid){
        return User::where("uid", '=', $uid)->first();
    }

    public function setPasswordAttribute($password)
    {
        if (strlen($password) !== 60 || !preg_match('/^\$2y\$/', $password)) {
            $this->attributes['password'] = bcrypt($password);
        } else {
            $this->attributes['password'] = $password;
        }
    }


    public function getLanguageCode()
    {
        return $this->language ? $this->language->code : null;
    }

    public function getLanguageCodeFull()
    {
        $region_code = $this->language->region_code ? strtoupper($this->language->region_code) : strtoupper($this->language->code);
        return $this->language ? ($this->language->code.'-'.$region_code) : null;
    }



    public function language()
    {
        return $this->belongsTo('App\Models\Lang');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo('App\Models\Shop','shop_id','id');
    }

    public static function scopeFilter($query, $request)
    {
        // filters
        $filters = $request->all();
        if (!empty($filters)) {
        }

    }

    public function tickets() : HasMany {
        return $this->hasMany('App\Models\Ticket\Ticket');
    }

    public function getFullNameAttribute()
    {
        return "{$this->firstname} {$this->lastname}";
    }

    public function getImageAttribute()
    {
        return asset('images/default-user.png');
    }



    public function subscriptions()
    {
        return $this->hasMany('App\Models\Subscription');
    }

    public function generalSubscriptions()
    {
        return $this->subscriptions()->general();
    }

    public function getNewGeneralSubscription()
    {
        // ONLY one new subscription allowed
        $subscriptions = $this->generalSubscriptions()->new();

        if ($subscriptions->count() > 1) {
            throw new Exception('There are 2 subscriptions of [new] status. Please clean up the DB');
        }

        return $subscriptions->first();
    }

    public function getLastCancelledOrEndedGeneralSubscription()
    {
        // Lấy subscription ended gần đây nhất. Null nếu chưa đăng ký bao giờ
        return $this->generalSubscriptions()->cancelledOrEdned()->orderBy('created_at', 'desc')->first();
    }

    public function contact()
    {
        return $this->belongsTo('App\Models\Contact');
    }

    public function mailLists()
    {
        return $this->hasMany('App\Models\Campaign\CampaignMaillist');
    }

    public function user()
    {
        return $this->hasOne('App\Models\User');
    }

    public function admin()
    {
        return $this->belongsTo('App\Models\Admin');
    }

    public function lists()
    {
        return $this->hasMany('App\Models\MailList');
    }

    public function templates()
    {
        return $this->hasMany('App\Models\Template');
    }


    public function campaigns()
    {
        return $this->hasMany('App\Models\Campaign');
    }

    public function sentCampaigns()
    {
        return $this->hasMany('App\Models\Campaign')->where('status', '=', 'done')->orderBy('created_at', 'desc');
    }

    public function subscribers()
    {
        return $this->hasManyThrough('App\Models\Subscriber', 'App\Models\MailList');
    }

    public function logs()
    {
        return $this->hasMany('App\Models\Log')->orderBy('created_at', 'desc');
    }

    public function trackingLogs()
    {
        return $this->hasMany('App\Models\TrackingLog')->orderBy('created_at', 'asc');
    }

    public function automation2s()
    {
        return $this->hasMany('App\Models\Automation2');
    }

    public function activeAutomation2s()
    {
        return $this->hasMany('App\Models\Automation2')->where('status', Automation2::STATUS_ACTIVE);
    }

    public function sendingDomains()
    {
        return $this->hasMany('App\Models\SendingDomain');
    }

    public function emailVerificationServers()
    {
        return $this->hasMany('App\Models\EmailVerificationServer');
    }

    public function activeEmailVerificationServers()
    {
        return $this->emailVerificationServers()->where('status', '=', EmailVerificationServer::STATUS_ACTIVE);
    }

    public function blacklists()
    {
        return $this->hasMany('App\Models\Blacklist');
    }

    // Only direct senders children
    public function senders()
    {
        return $this->hasMany('App\Models\Sender');
    }

    // tracking domain
    public function trackingDomains()
    {
        return $this->hasMany('App\Models\TrackingDomain');
    }

    public function products()
    {
        return $this->hasMany('App\Models\Product');
    }

    public function invoices()
    {
        return $this->hasMany('App\Models\Invoice');
    }

    public function forms()
    {
        return $this->hasMany('App\Models\Form');
    }

    public function websites()
    {
        return $this->hasMany('App\Models\Website');
    }

    /**
     * Get user sources.
     */
    public function sources()
    {
        return $this->hasmany('App\Models\Source');
    }

    // billing addresses
    public function billingAddresses()
    {
        return $this->hasMany('App\Models\BillingAddress');
    }

    public function getBasePath($path = null)
    {
        $base = storage_path(join_paths(self::BASE_DIR, $this->uid)); // storage/app/customers/000000/

        if (!\Illuminate\Support\Facades\File::exists($base)) {
            \Illuminate\Support\Facades\File::makeDirectory($base, 0777, true, true);
        }

        return join_paths($base, $path);
    }

    public function getTemplatesPath($path = null)
    {
        $base = $this->getBasePath(self::TEMPLATES_DIR);

        if (!\Illuminate\Support\Facades\File::exists($base)) {
            \Illuminate\Support\Facades\File::makeDirectory($base, 0777, true, true);
        }

        return join_paths($base, $path);
    }

    public function getProductsPath($path = null)
    {
        $base = $this->getBasePath(self::PRODUCT_DIR);

        if (!\Illuminate\Support\Facades\File::exists($base)) {
            \Illuminate\Support\Facades\File::makeDirectory($base, 0777, true, true);
        }

        return join_paths($base, $path);
    }

    public function getAttachmentsPath($path = null)
    {
        $base = $this->getBasePath(self::ATTACHMENTS_DIR);

        if (!\Illuminate\Support\Facades\File::exists($base)) {
            \Illuminate\Support\Facades\File::makeDirectory($base, 0777, true, true);
        }

        return join_paths($base, $path);
    }

    public function getLogPath($path = null)
    {
        $base = $this->getBasePath(self::LOGS_DIR);

        if (!\Illuminate\Support\Facades\File::exists($base)) {
            \Illuminate\Support\Facades\File::makeDirectory($base, 0777, true, true);
        }

        return join_paths($base, $path);
    }

    /**
     * Items per page.
     *
     * @var array
     */
    public static $itemsPerPage = 25;

    public static function scopeSearch($query, $keyword)
    {
        $query = $query->select('customers.*')
            ->leftJoin('users', 'users.customer_id', '=', 'customers.id');

        // Keyword
        if (!empty(trim($keyword))) {
            foreach (explode(' ', trim($keyword)) as $keyword) {
                $query = $query->where(function ($q) use ($keyword) {
                    $q->orwhere('users.first_name', 'like', '%'.$keyword.'%')
                        ->orWhere('users.last_name', 'like', '%'.$keyword.'%')
                        ->orWhere('users.email', 'like', '%'.$keyword.'%');
                });
            }
        }
    }

    /**
     * Subscribers count by time.
     *
     * @return number
     */
    public static function subscribersCountByTime($begin, $end, $customer_id = null, $list_id = null, $status = null)
    {
        $query = \App\Models\Subscriber::leftJoin('mail_lists', 'mail_lists.id', '=', 'subscribers.mail_list_id')
            ->leftJoin('customers', 'customers.id', '=', 'mail_lists.customer_id');

        if (isset($list_id)) {
            $query = $query->where('subscribers.mail_list_id', '=', $list_id);
        }
        if (isset($customer_id)) {
            $query = $query->where('customers.id', '=', $customer_id);
        }
        if (isset($status)) {
            $query = $query->where('subscribers.status', '=', $status);
        }

        $query = $query->where('subscribers.created_at', '>=', $begin)
            ->where('subscribers.created_at', '<=', $end);

        return $query->count();
    }

    public function getCurrentActiveSubscription()
    {
        // Keep this function alias to support plugins
        return $this->getCurrentActiveGeneralSubscription();
    }

    /**
     * Count customer lists.
     *
     * @return number
     */
    public function listsCount()
    {
        return $this->lists()->count();
    }

    /**
     * Count customer's campaigns.
     *
     * @return number
     */
    public function campaignsCount()
    {
        return $this->campaigns()->count();
    }


    /**
     * Get subscriber quota.
     *
     * @return number
     */
    public function maxSubscribers()
    {
        $count = get_tmp_quota($this, 'subscriber_max');
        if ($count == -1) {
            return '∞';
        } else {
            return $count;
        }
    }

    /**
     * Count customer's subscribers.
     *
     * @return number
     */
    public function subscribersCount($cache = false)
    {
        if ($cache) {
            return $this->readCache('SubscriberCount');
        }

        // return distinctCount($this->subscribers(), 'subscribers.email', 'distinct');
        return $this->subscribers()->count();
    }

    /**
     * Calculate subscibers usage.
     *
     * @return number
     */
    public function subscribersUsage($cache = false)
    {
        $max = $this->maxSubscribers();
        $count = $this->subscribersCount($cache);

        if ($max == '∞') {
            return 0;
        }
        if ($max == 0) {
            return 0;
        }
        if ($count > $max) {
            return 100;
        }

        return round((($count / $max) * 100), 2);
    }

    /**
     * Calculate subscibers usage.
     *
     * @return number
     */
    public function displaySubscribersUsage()
    {
        if ($this->maxSubscribers() == '∞') {
            return trans('messages.unlimited');
        }

        return $this->readCache('SubscriberUsage', 0).'%';
    }

    /**
     * Get customer's quota.
     *
     * @return string
     */
    public function maxQuota()
    {
        $quota = get_tmp_quota($this, 'sending_quota');
        if ($quota == '-1') {
            return '∞';
        } else {
            return $quota;
        }
    }

    /**
     * Get customer's color scheme.
     *
     * @return string
     */
    public function getColorScheme()
    {
        // Store mode support only sms theme
        if (config('app.store')) {
            return 'store';
        }

        if (!empty($this->color_scheme)) {
            return $this->color_scheme;
        } else {
            return \App\Models\Setting::get('frontend_scheme');
        }
    }

    /**
     * Color array.
     *
     * @return array
     */
    public static function colors($default)
    {
        return [
            ['value' => 'default', 'text' => trans('messages.system_default')],
            ['value' => 'blue', 'text' => trans('messages.blue')],
            ['value' => 'green', 'text' => trans('messages.green')],
            ['value' => 'brown', 'text' => trans('messages.brown')],
            ['value' => 'pink', 'text' => trans('messages.pink')],
            ['value' => 'grey', 'text' => trans('messages.grey')],
            ['value' => 'white', 'text' => trans('messages.white')],
        ];
    }

    /**
     * Disable customer.
     *
     * @return bool
     */
    public function disable()
    {
        $this->status = 'inactive';

        return $this->save();
    }

    /**
     * Enable customer.
     *
     * @return bool
     */
    public function enable()
    {
        $this->status = 'active';

        return $this->save();
    }

    /**
     * Get customer timezone.
     *
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }


    /**
     * Get customer select2 select options.
     *
     * @return array
     */
    public static function select2($request)
    {
        $data = ['items' => [], 'more' => true];

        $query = self::select('customers.*')->leftJoin('users', 'users.customer_id', '=', 'customers.id');
        if (isset($request->q)) {
            $keyword = $request->q;
            $query = $query->where(function ($q) use ($keyword) {
                $q->orwhere('users.first_name', 'like', '%'.$keyword.'%')
                    ->orWhere('users.last_name', 'like', '%'.$keyword.'%')
                    ->orWhere('users.email', 'like', '%'.$keyword.'%');
            });
        }

        // Read all check
        if (!$request->user()->admin->can('readAll', new \App\Models\Customer())) {
            $query = $query->where('customers.admin_id', '=', $request->user()->admin->id);
        }

        foreach ($query->limit(20)->get() as $customer) {
            $data['items'][] = ['id' => $customer->uid, 'text' => $customer->displayNameEmailOption()];
        }

        return json_encode($data);
    }

    /**
     * Create/Update customer information.
     *
     * @return object
     */
    public function createAccountAndUser($request)
    {
        $user = new \App\Models\User();

        DB::transaction(function () use ($request, &$user) {
            // Customer
            $this->fill($request->all());
            $this->status = self::STATUS_ACTIVE;
            $this->save();

            // User
            $user = new User();
            $user->fill($request->all());
            $user->password = bcrypt($request->password);
            $user->customer()->associate($this);
            $user->save();
        });

        // Important: return the newly created USER
        return $user;
    }

    public function sendingServers()
    {
        return $this->hasMany('App\Models\SendingServer');
    }

    public function subAccounts()
    {
        return $this->hasMany('App\Models\SubAccount');
    }

    /**
     * Customers count by time.
     *
     * @return number
     */
    public static function customersCountByTime($begin, $end, $admin = null)
    {
        $query = \App\Models\Customer::select('customers.*');

        if (isset($admin) && !$admin->can('readAll', new \App\Models\Customer())) {
            $query = $query->where('customers.admin_id', '=', $admin->id);
        }

        $query = $query->where('customers.created_at', '>=', $begin)
            ->where('customers.created_at', '<=', $end);

        return $query->count();
    }

    public function getSubscriberCountByStatus($status)
    {
        // @note: in this particular case, a simple count(distinct) query is much more efficient
        $query = $this->subscribers()->where('subscribers.status', $status)->distinct('subscribers.email');

        return $query->count();
    }

    /**
     * Update Campaign cached data.
     */
    public function getCacheIndex()
    {
        // cache indexes
        return [
            // @note: SubscriberCount must come first as its value shall be used by the others
            'SubscriberCount' => function () {
                return $this->subscribersCount(false);
            },
            'SubscriberUsage' => function () {
                return $this->subscribersUsage(true);
            },
            'SubscribedCount' => function () {
                return $this->getSubscriberCountByStatus(\App\Models\Subscriber::STATUS_SUBSCRIBED);
            },
            'UnsubscribedCount' => function () {
                return $this->getSubscriberCountByStatus(Subscriber::STATUS_UNSUBSCRIBED);
            },
            'UnconfirmedCount' => function () {
                return $this->getSubscriberCountByStatus(Subscriber::STATUS_UNCONFIRMED);
            },
            'BlacklistedCount' => function () {
                return $this->getSubscriberCountByStatus(Subscriber::STATUS_BLACKLISTED);
            },
            'SpamReportedCount' => function () {
                return $this->getSubscriberCountByStatus(Subscriber::STATUS_SPAM_REPORTED);
            },
            'MailListSelectOptions' => function () {
                return $this->getMailListSelectOptions([], true);
            },
            'Bounce/FeedbackRate' => function () {
                return $this->getBounceFeedbackRate();
            },
        ];
    }

    public function getBounceFeedbackRate()
    {
        $delivery = $this->trackingLogs()->count();

        if ($delivery == 0) {
            return 0;
        }

        $bounce = DB::table('bounce_logs')->leftJoin('tracking_logs', 'tracking_logs.message_id', '=', 'bounce_logs.message_id')->count();
        $feedback = DB::table('feedback_logs')->leftJoin('tracking_logs', 'tracking_logs.message_id', '=', 'feedback_logs.message_id')->count();

        $percentage = ($feedback + $bounce) / $delivery;
    }

    /**
     * Sending servers count.
     *
     * @var int
     */
    public function sendingServersCount()
    {
        return $this->sendingServers()->count();
    }

    /**
     * Sending domains count.
     *
     * @var int
     */
    public function sendingDomainsCount()
    {
        return $this->sendingDomains()->count();
    }

    /**
     * Get max sending server count.
     *
     * @var int
     */
    public function maxSendingServers()
    {
        $count = get_tmp_quota($this, 'sending_servers_max');
        if ($count == -1) {
            return '∞';
        } else {
            return $count;
        }
    }

    /**
     * Get max email verification server count.
     *
     * @var int
     */
    public function maxEmailVerificationServers()
    {
        $count = get_tmp_quota($this, 'email_verification_servers_max');
        if ($count == -1) {
            return '∞';
        } else {
            return $count;
        }
    }

    /**
     * Calculate email verification server usage.
     *
     * @return number
     */
    public function emailVerificationServersUsage()
    {
        $max = $this->maxEmailVerificationServers();
        $count = $this->emailVerificationServersCount();

        if ($max == '∞') {
            return 0;
        }
        if ($max == 0) {
            return 0;
        }
        if ($count > $max) {
            return 100;
        }

        return round((($count / $max) * 100), 2);
    }

    /**
     * Calculate email verigfication servers usage.
     *
     * @return number
     */
    public function displayEmailVerificationServersUsage()
    {
        if ($this->maxEmailVerificationServers() == '∞') {
            return trans('messages.unlimited');
        }

        return $this->emailVerificationServersUsage().'%';
    }

    /**
     * Calculate sending servers usage.
     *
     * @return number
     */
    public function sendingServersUsage()
    {
        $max = $this->maxSendingServers();
        $count = $this->sendingServersCount();

        if ($max == '∞') {
            return 0;
        }
        if ($max == 0) {
            return 0;
        }
        if ($count > $max) {
            return 100;
        }

        return round((($count / $max) * 100), 2);
    }

    /**
     * Calculate sending servers usage.
     *
     * @return number
     */
    public function displaySendingServersUsage()
    {
        if ($this->maxSendingServers() == '∞') {
            return trans('messages.unlimited');
        }

        return $this->sendingServersUsage().'%';
    }

    /**
     * Get max sending server count.
     *
     * @var int
     */
    public function maxSendingDomains()
    {
        $count = get_tmp_quota($this, 'sending_domains_max');
        if ($count == -1) {
            return '∞';
        } else {
            return $count;
        }
    }

    /**
     * Calculate subscibers usage.
     *
     * @return number
     */
    public function sendingDomainsUsage()
    {
        $max = $this->maxSendingDomains();
        $count = $this->sendingDomainsCount();

        if ($max == '∞') {
            return 0;
        }
        if ($max == 0) {
            return 0;
        }
        if ($count > $max) {
            return 100;
        }

        return round((($count / $max) * 100), 2);
    }

    /**
     * Count customer automations.
     *
     * @return number
     */
    public function automationsCount()
    {
        return $this->automation2sCount();
    }

    /**
     * Check if customer has admin account.
     *
     * @return bool
     */
    public function hasAdminAccount()
    {
        return $this->user && $this->user->admin;
    }

    /**
     * Get all customer active sending servers.
     *
     * @return collect
     */
    public function activeSendingServers()
    {
        return $this->sendingServers()->where('status', '=', \App\Models\SendingServer::STATUS_ACTIVE);
    }

    /**
     * Check if customer is disabled.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->status == self::STATUS_ACTIVE;
    }

    /**
     * Get total file size usage.
     *
     * @return number
     */
    public function totalUploadSize()
    {
        return \Acelle\Library\Tool::getDirectorySize(base_path('public/source/'.$this->user->uid)) / 1048576;
    }

    /**
     * Get max upload size quota.
     *
     * @return number
     */
    public function maxTotalUploadSize()
    {
        $count = get_tmp_quota($this, 'max_size_upload_total');
        if ($count == -1) {
            return '∞';
        } else {
            return $count;
        }
    }

    /**
     * Calculate campaign usage.
     *
     * @return number
     */
    public function totalUploadSizeUsage()
    {
        if ($this->maxTotalUploadSize() == '∞') {
            return 0;
        }
        if ($this->maxTotalUploadSize() == 0) {
            return 100;
        }

        return round((($this->totalUploadSize() / $this->maxTotalUploadSize()) * 100), 2);
    }


    /**
     * Get customer contact.
     *
     * @return Contact
     */
    public function getContact()
    {
        if ($this->contact) {
            $contact = $this->contact;
        } else {
            $contact = new \App\Models\Contact([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->user->email,
            ]);
        }

        return $contact;
    }

    /*
     * Custom name + email.
     *
     * @return string
     */
    public function displayNameEmailOption()
    {
        return $this->displayName().'|||'.$this->user->email;
    }

    /**
     * Email verification servers count.
     *
     * @var int
     */
    public function emailVerificationServersCount()
    {
        return $this->emailVerificationServers()->count();
    }

    /**
     * Get list of available email verification servers.
     *
     * @var bool
     */
    public function getEmailVerificationServers()
    {
        if (!config('app.saas')) {
            return EmailVerificationServer::getAllAdminActive()->get()->map(function ($server) {
                return $server;
            });
        } elseif ($this->getCurrentActiveGeneralSubscription()->planGeneral->useOwnEmailVerificationServer()) {
            return $this->activeEmailVerificationServers()->get()->map(function ($server) {
                return $server;
            });
            // If customer dont have permission creating sending servers
        } else {
            // Get server from the plan
            return  $this->getCurrentActiveGeneralSubscription()->planGeneral->getEmailVerificationServers();
        }
    }

    /**
     * Get the list of available mail lists, used for populating select box.
     *
     * @return array
     */
    public function getMailListSelectOptions($options = [], $cache = false)
    {
        $query = $this->mailLists();
       // $query = $this->mailLists();

        # Other list
        if (isset($options['other_list_of'])) {
            $query->where('id', '!=', $options['other_list_of']);
        }

        $result = $query->orderBy('name')->get()->map(function ($item) use ($cache) {
            return ['id' => $item->id, 'value' => $item->uid, 'text' => $item->name.' ('.$item->subscribersCount($cache).' '.strtolower(trans('messages.subscribers')).')'];
        });

        return $result;
    }

    /**
     * Get email verification servers select options.
     *
     * @return array
     */
    public function emailVerificationServerSelectOptions()
    {
        $servers = $this->getEmailVerificationServers();
        $options = [];
        foreach ($servers as $server) {
            $options[] = ['text' => $server->name, 'value' => $server->uid];
        }

        return $options;
    }

    /**
     * Get customer's sending servers type.
     *
     * @return array
     */
    public function getSendingServertypes()
    {
        $allTypes = \App\Models\SendingServer::types();
        $types = [];

        foreach ($allTypes as $type => $server) {
            if ($this->isAllowCreateSendingServerType($type)) {
                $types[$type] = $server;
            }
        }

        if (!Setting::isYes('delivery.sendmail')) {
            unset($types['sendmail']);
        }

        if (!Setting::isYes('delivery.phpmail')) {
            unset($types['php-mail']);
        }

        return $types;
    }

    /**
     * Check customer can create sending servers type.
     *
     * @return bool
     */
    public function isAllowCreateSendingServerType($type)
    {
        $customerTypes = get_tmp_quota($this, 'sending_server_types');
        if (get_tmp_quota($this, 'all_sending_server_types') == 'yes' ||
            (isset($customerTypes[$type]) && $customerTypes[$type] == 'yes')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Add email to customer's blacklist.
     */
    public function addEmaillToBlacklist($email)
    {
        $email = trim(strtolower($email));

        if (\Acelle\Library\Tool::isValidEmail($email)) {
            $exist = $this->blacklists()->where('email', '=', $email)->count();
            if (!$exist) {
                $blacklist = new \App\Models\Blacklist();
                $blacklist->customer_id = $this->id;
                $blacklist->email = $email;
                $blacklist->save();
            }
        }
    }

    /**
     * Check if customer has api access.
     *
     * @return bool
     */
    public function canUseApi()
    {
        return get_tmp_quota($this, 'api_access') == 'yes';
    }

    /**
     * Get all verified identities.
     *
     * @return array
     */
    public function verifiedIdentitiesDroplist($keyword = null)
    {
        $droplist = [];
        $topList = [];
        $bottomList = [];

        if (!$keyword) {
            $keyword = '###';
        }

        foreach ($this->getVerifiedIdentities() as $item) {
            // check if email
            if (extract_email($item) !== null) {
                $email = extract_email($item);
                if (strpos(strtolower($email), $keyword) === 0) {
                    $topList[] = [
                        'text' => extract_name($item),
                        'value' => $email,
                        'desc' => str_replace($keyword, '<span class="text-semibold text-primary"><strong>'.$keyword.'</strong></span>', $email),
                    ];
                } else {
                    $bottomList[] = [
                        'text' => extract_name($item),
                        'value' => $email,
                        'desc' => $email,
                    ];
                }
            } else { // domains are alse
                $dKey = explode('@', $keyword);
                $eKey = $dKey[0];
                $dKey = isset($dKey[1]) ? $dKey[1] : null;
                // if ( (!isset($dKey) || $dKey == '') || ($dKey && strpos(strtolower($item), $dKey) === 0 )) {
                if ($keyword == '###') {
                    $eKey = '****';
                }
                $topList[] = [
                    'text' => $eKey.'@'.str_replace($dKey, '<span class="text-semibold text-primary"><strong>'.$dKey.'</strong></span>', $item),
                    'subfix' => $item,
                    'desc' => null,
                ];
                // }
            }
        }

        $droplist = array_merge($topList, $bottomList);

        return $droplist;
    }

    /**
     * Count customer automation2s.
     *
     * @return number
     */
    public function automation2sCount()
    {
        return $this->automation2s()->count();
    }

    public function getLockPath($path)
    {
        return $this->user->getLockPath($path);
    }

    public function allowUnverifiedFromEmailAddress()
    {
        $server = null;
        if (!config('app.saas')) {
            $server = get_tmp_primary_server();
        } else {
            $plan = $this->getNewOrActiveGeneralSubscription()->planGeneral;

            if ($plan->useSystemSendingServer()) {
                $server = $plan->primarySendingServer();
            }
        }

        return (is_null($server)) ? true : $server->allowUnverifiedFromEmailAddress();
    }

    /**
     * Get list of available tracking domain options.
     *
     * @var bool
     */
    public function getVerifiedTrackingDomainOptions()
    {
        return $this->trackingDomains()->verified()->get()->map(function ($domain) {
            return ['value' => $domain->uid, 'text' => $domain->name];
        });
    }

    // Get the current time in Customer timezone
    public function getCurrentTime()
    {
        return Carbon::now($this->timezone);
    }

    public function parseDateTime($datetime, $fallback = false)
    {
        // IMPORTANT: datetime string must NOT contain timezone information
        // IMPORTANT: passing [$this->timezone] is critically needed, to make sure this function works in console
        try {
            $dt = Carbon::parse($datetime, $this->timezone);
            $dt = $dt->timezone($this->timezone);
        } catch (\Exception $ex) {
            // parse special chars, for example ('sadfh&#($783943') ==> exception
            if ($fallback) {
                $dt = $this->parseDateTime('1900-01-01');
            } else {
                throw $ex;
            }
        }
        return $dt;
    }

    public function formatCurrentDateTime($name)
    {
        return $this->formatDateTime($this->getCurrentTime(), $name);
    }

    public function formatDateTime(Carbon $datetime, string $name)
    {
        // $name is a format name like: date_full | date_short
        // See config('localization')['*'] for the full list of available format names
        return format_datetime($datetime->timezone($this->getTimezone()), $name, $this->getLanguageCode());
    }

    /**
     * Get customer product source.
     *
     * @var collect
     */
    public function newProductSource($type)
    {
        $class = '\\Acelle\\Model\\' . $type;
        $source = new $class();
        $source->customer_id = $this->id;
        $source->type = $type;

        return $source;
    }

    /**
     * Get customer product source.
     *
     * @var collect
     */
    public function findProductSource($type)
    {
        $source = $this->sources()
            ->where('type', '=', $type)
            ->first();

        if (!$source) {
            $source = new Source();
            $source->customer_id = $this->id;
            $source->type = $type;
        }

        return $source;
    }

    /**
     * Get source select options.
     *
     * @return object
     */
    public function getSelectOptions($type = null)
    {
        $query = $this->sources();

        if ($type) {
            $query = $query->where('type', '=', $type);
        }

        return $query->get()->map(function ($source) {
            return ['text' => $source->getData()['data']['name'], 'value' => $source->uid];
        });
    }

    /**
     * Get payment method.
     *
     * @var object | collect
     */
    public function getPreferredPaymentGateway()
    {
        // Value of "payment_method" was every time user chooses a payment method in the web UI
        // See SubscriptionController@checkout() function where it is set
        if (!$this['payment_method']) {
            return null;
        }

        // For example, the user preferred method is "paddle", but paddle is no longer available
        // Then simply return null
        // So, in the web UI, this user does not have a preferred payment method
        $meta = json_decode($this['payment_method'], true);

        if (!array_key_exists('method', $meta)) {
            throw new Exception("The 'method' key is required for 'preferred payment' data");
        }

        $type = $meta['method'];

        if ($type && Billing::isGatewayRegistered($type)) {
            return Billing::getGateway($type);
        } else {
            // payment method was previously stored but it is dropped in the current version
            return null;
        }
    }

    /**
     * Update payment method.
     *
     * @var object | collect
     */
    public function updatePaymentMethod($data = [])
    {
        // $paymentMethod = $this->getPaymentMethod();

        // if (!isset($paymentMethod)) {
        //     $paymentMethod = [];
        // }

        // $data = (object) array_merge((array) $paymentMethod, $data);
        $this['payment_method'] = json_encode($data);

        $this->save();
    }

    /**
     * Remove payment method.
     *
     * @var object | collect
     */
    public function removePaymentMethod()
    {
        $this->payment_method = null;
        $this->save();
    }

    /**
     * Get default billing address.
     *
     * @var object
     */
    public function newBillingAddress()
    {
        $address = new \App\Models\BillingAddress();
        $address->customer_id = $this->id;
        return $address;
    }

    /**
     * Get default billing address.
     *
     * @var object
     */
    public function getDefaultBillingAddress()
    {
        return $this->billingAddresses()->first();
    }

    public function createAbandonedEmailAutomation($store)
    {
        $auto = $this->automation2s()->create([
            'name' => 'Abandoned Cart Notification - Auto',
            'mail_list_id' => $store->getList()->id,
            'status' => 'inactive',
        ]);

        $email = new \App\Models\Email([
            'action_id' => '1000000001',
        ]);
        $email->customer_id = $this->id;
        $email->automation2_id = $auto->id;
        $email->save();

        $auto->data = json_encode([
            [
                "title" => "Abandoned Cart Reminder",
                "id" => "trigger",
                "type" => "ElementTrigger",
                "child" => "1000000001",
                "options" => [
                    "key" => "woo-abandoned-cart",
                    "type" => "woo-abandoned-cart",
                    "source_uid" => $store->uid,
                    "wait" => "24_hour",
                    "init"  => "true"
                ]
            ], [
                "title" => "Hey, you have an item left in cart",
                "id" => "1000000001",
                "type" => "ElementAction",
                "child" => null,
                "options" => [
                    "init" => "true",
                    "email_uid" => $email->uid
                ]
            ]
        ]);

        $auto->save();

        return $auto;
    }

    public function getAbandonedEmailAutomation($store)
    {
        $auto = $this->automation2s()->where('mail_list_id', '=', $store->mail_list_id)->first();
        if (!$auto) {
            $auto = $this->createAbandonedEmailAutomation($store);
        }
        return $auto;
    }

    /**
     * Get auto billing data.
     *
     * @var object
     */
    public function getAutoBillingData()
    {
        if ($this->auto_billing_data == null) {
            return null;
        }

        return AutoBillingData::fromJson($this->auto_billing_data);
    }

    /**
     * Get auto billing data.
     *
     * @var object
     */
    public function setAutoBillingData(AutoBillingData $autoBillingData)
    {
        $this->auto_billing_data = $autoBillingData->toJson();
        $this->save();
    }

    // Do not call delete() directly on Customer
    // This method helps delete Customer account but KEEP
    // the associated User account if it is also associated with an Admin
    public function deleteAccount()
    {
        DB::transaction(function () {
            $user = $this->user;
            $this->delete();
            if (!$user->admin()->exists()) {
                $user->deleteAndCleanup();
            }
        });
    }

    public function copyTemplateAs(Template $template, $name)
    {
        $copy = $template->copy([
            'name' => $name,
            'customer_id' => $this->id
        ]);

        return $copy;
    }

    /**
     * Get builder templates.
     *
     * @return mixed
     */
    public function getBuilderTemplates()
    {
        $result = [];

        // Gallery
        $templates = \App\Models\Template::where('customer_id', '=', $this->id)
            ->orWhere(function ($query) {
                $query->notPreserved();
            })
            ->orderBy('customer_id')
            ->notPreserved()
            ->get();

        foreach ($templates as $template) {
            $result[] = [
                'name' => $template->name,
                'url' => '', // action('CampaignController@templateChangeTemplate', ['uid' => $this->uid, 'template_uid' => $template->uid]),
                'thumbnail' => $template->getThumbUrl(),
            ];
        }

        return $result;
    }

    public function preferredPaymentGatewayCanAutoCharge()
    {
        // check auto billing data
        if ($this->getPreferredPaymentGateway() == null) {
            return false;
        }

        // support auto billing
        if (!$this->getPreferredPaymentGateway()->supportsAutoBilling()) {
            return false;
        }

        // no autobilling data
        if ($this->getAutoBillingData() == null || $this->getAutoBillingData()->getGateway() == null) {
            return false;
        }

        // check payment
        if ($this->getPreferredPaymentGateway()->getType() != $this->getAutoBillingData()->getGateway()->getType()) {
            return false;
        }

        return true;
    }

    public function importBlacklistJobs()
    {
        return $this->jobMonitors()->orderBy('job_monitors.id', 'DESC')->where('job_type', ImportBlacklistJob::class);
    }

    public function getMenuLayout()
    {
        return ($this->menu_layout == 'left' ? 'left' : 'top');
    }

    public function allowVerifyingOwnDomains()
    {
        if (config('app.saas')) {
            $subscription = $this->getCurrentActiveGeneralSubscription();
            $server = get_tmp_primary_server();
        } else {
            $server = get_tmp_primary_server();
        }

        if ($server && ($server->allowVerifyingOwnDomains() || $server->allowVerifyingOwnDomainsRemotely())) {
            return true;
        } else {
            return false;
        }
    }

    public function getConnectedWebsiteSelectOptions($long = true)
    {
        $query = $this->websites()->connected();

        $result = $query->orderBy('title')->get()->map(function ($item) use ($long) {
            if ($long) {
                return ['value' => $item->uid, 'text' => '<span class="fw-600">' . $item->title . '</span><br><span class="text-muted">' . $item->url . '</span>'];
            } else {
                return ['value' => $item->uid, 'text' => $item->title];
            }
        });

        return $result;
    }

    public function updateBillingInformationFromInvoice($invoice)
    {
        $billingAddress = $this->getDefaultBillingAddress();

        // has no address yet
        if (!$billingAddress) {
            $billingAddress = $this->newBillingAddress();
        }

        $billingAddress->fill([
            'first_name' => $invoice->billing_first_name,
            'last_name' => $invoice->billing_last_name,
            'address' => $invoice->billing_address,
            'email' => $invoice->billing_email,
            'phone' => $invoice->billing_phone,
            'country_id' => $invoice->billing_country_id,
        ]);

        $billingAddress->save();
    }

    public static function newCustomer()
    {
        $customer = new self();
        $customer->menu_layout = \App\Models\Setting::get('layout.menu_bar');

        return $customer;
    }

    public function newProduct()
    {
        $product = new \App\Models\Product();
        $product->customer_id = $this->id;

        return $product;
    }

    public static function scopeByPlan($query, $plan)
    {
        $query = $query->whereHas('subscriptions', function ($q) use ($plan) {
            $q->newOrActive()->where('plan_id', '=', $plan->id);
        });
    }

    public function displayName()
    {
        $lastNameFirst = get_localization_config('show_last_name_first', $this->getLanguageCode());

        if ($lastNameFirst) {
            return htmlspecialchars(trim($this->user->last_name.' '.$this->user->first_name));
        } else {
            return htmlspecialchars(trim($this->user->first_name.' '.$this->user->last_name));
        }
    }

    public function newDefaultCampaign()
    {
        $campaign = Campaign::newDefault();
        $campaign->customer_id = $this->id;

        return $campaign;
    }


    /*
     *
     * FUNCTIONS THAT ARE DEPENDENT ON PLAN/SUBSCRIPTIONS
     *
     */

    public function getCurrentActiveGeneralSubscription()
    {
        if (!config('app.saas')) {
            throw new Exception('Operation not allowed in NON-SAAS mode');
        }

        // only ONE
        $subscriptions = $this->generalSubscriptions()->active();

        if ($subscriptions->count() > 1) {
            throw new Exception('There are 2 subscriptions of [active] status. Please clean up the DB');
        }

        return $subscriptions->first();
    }

    public function isCustomTrackingDomainRequired()
    {
        if (!config('app.saas')) {
            return false;
        }

        $plan = $this->getNewOrActiveGeneralSubscription()->planGeneral;

        if (!$plan) {
            return false;
        }

        return $plan->isCustomTrackingDomainRequired();
    }

    // Customer has only ONE general subscription which is NEW or ACTIVE
    public function getNewOrActiveGeneralSubscription()
    {
        $subscriptions = $this->generalSubscriptions()->newOrActive();

        if ($subscriptions->count() > 1) {
            throw new Exception('There are 2 subscriptions of [active, new] status. Please clean up the DB');
        }

        return $subscriptions->first();
    }

    // MISS FIRST primary sending server's identities here
    public function getVerifiedIdentities()
    {
        $list = [];

        // own emails
        if (!config('app.saas') || $this->getCurrentActiveGeneralSubscription()->planGeneral->allowSenderVerification()) {
            $senders = $this->senders()->verified()->get();
            foreach ($senders as $sender) {
                $list[] = $sender->name.' <'.$sender->email.'>';
            }
        }

        // own domain
        $domains = $this->sendingDomains()->active();

        if (config('app.saas')) {
            $plan = $this->getCurrentActiveGeneralSubscription()->planGeneral;
            if ($plan->useSystemSendingServer()) {
                $server = $plan->primarySendingServer();
                if (!$server->allowOtherSendingDomains()) {
                    $domains->bySendingServer($server);
                }
            }
        }

        foreach ($domains->get() as $domain) {
            $list[] = $domain->name;
        }

        if (config('app.saas')) {
            // plan sending server emails if system sending servers
            if ($plan->useSystemSendingServer()) {
                $list = array_merge($plan->getVerifiedIdentities(), $list);
            }
        }

        return array_values(array_unique($list));
    }

    public function assignGeneralPlan($plan)
    {
        if ($this->getNewOrActiveGeneralSubscription()) {
            throw new \Exception(trans('messages.customer.already_new_active_sub'));
        }

        // Put in transaction. Make sure senderID always with subscription
        $subscription = \DB::transaction(function () use ($plan) {
            // allways create init subscription with init invoice (by design)
            $subscription = Subscription::createNewSubscription($this, $plan);

            if ($plan->hasTrial()) {
                $invoice = $subscription->createNewSubscriptionInvoiceWithTrial();
            } else {
                $invoice = $subscription->createNewSubscriptionInvoiceWithoutTrial();
            }

            // Assign sending credits
            // Notice that the following method is available for a SubscriptionGeneralPlan only
            $subscription->setDefaultEmailCredits();

            // log
            SubscriptionFacade::log($subscription, SubscriptionLog::TYPE_SELECT_PLAN, $invoice->uid, [
                'plan' => $subscription->getPlanName(),
                'customer' => $subscription->getCustomerName(),
                'amount' => $invoice->total(),
            ]);

            return $subscription;
        });

        return $subscription;
    }

    /**
     * Check customer if has notice.
     */
    public function hasSubscriptionNotice()
    {
        if (!config('app.saas')) {
            return false;
        }

        $subscription = $this->getNewOrActiveGeneralSubscription();

        if (is_null($subscription)) {
            return false;
        }


        if ($subscription->getUnpaidInvoice()) {
            return true;
        }

        return false;
    }


}

