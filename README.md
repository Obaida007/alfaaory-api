# متجر الفاعوري — الخلفية (API + لوحة التحكم)

الخلفية الكاملة لمتجر **الفاعوري** ([alfaore-store.com](https://alfaore-store.com)): شحن الألعاب والاشتراكات والبطاقات الإلكترونية والأرصدة. الدفع **من رصيد المحفظة فقط** ولا توجد عربة شراء: يختار المستخدم الباقة، ويُدخل معرّف اللاعب، ويشتري مباشرة.

| المكوّن | التقنية |
|---|---|
| الإطار | Laravel 13 · PHP 8.3+ |
| قاعدة البيانات | MySQL 8 (InnoDB، utf8mb4) |
| توثيق الـ API | Laravel Sanctum (Bearer Token) |
| لوحة التحكم | Filament 5 على `/admin` (عربي، RTL) |
| صلاحيات المشرفين | spatie/laravel-permission |
| توثيق OpenAPI | dedoc/scramble على `/docs/api` |
| الاختبارات | Pest 4 |

---

## التثبيت

```bash
git clone <repo> alfaore-api && cd alfaore-api
composer install
cp .env.example .env
php artisan key:generate
```

عدّل `.env`:

```dotenv
DB_CONNECTION=mysql
DB_DATABASE=alfaore          # أنشئها: CREATE DATABASE alfaore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
DB_USERNAME=root
DB_PASSWORD=

ADMIN_EMAIL=you@example.com  # حساب super_admin الذي يُنشئه الـ seeder
ADMIN_PASSWORD=كلمة-سر-قوية

FRONTEND_URL=http://localhost:3000   # رابط واجهة Next.js (لـ CORS وروابط استعادة كلمة المرور)
```

ثم:

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

> لا توجد أي كلمة سر ثابتة في الكود؛ حساب المدير يُنشأ من `ADMIN_EMAIL` و`ADMIN_PASSWORD`. إذا لم تحددهما سيتخطّى الـ seeder إنشاء الحساب.

### تشغيل الطوابير (Queue)

الطلبات من نوع **تسليم تلقائي** تُرسَل إلى المزوّد عبر Job في الطابور:

```bash
php artisan queue:work
```

للتطوير السريع يمكن وضع `QUEUE_CONNECTION=sync` في `.env`.

---

## الروابط

| الرابط | الوصف |
|---|---|
| `http://localhost:8000/admin` | لوحة التحكم |
| `http://localhost:8000/docs/api` | توثيق الـ API التفاعلي |
| `http://localhost:8000/docs/api.json` | ملف OpenAPI (JSON) |
| `http://localhost:8000/api/v1/...` | الـ API |

التوثيق متاح دائمًا في بيئة `local`. في الإنتاج يراه المشرفون المسجّلون فقط، إلا إذا وضعت `API_DOCS_PUBLIC=true`.

### توليد أنواع TypeScript للواجهة

```bash
npx openapi-typescript http://localhost:8000/docs/api.json -o types/api.ts
```

أو صدّر الملف دون تشغيل السيرفر:

```bash
php artisan scramble:export --path=api.json
npx openapi-typescript ./api.json -o types/api.ts
```

---

## شكل الاستجابة

كل استجابة من `/api/v1` لها الشكل نفسه:

```json
{ "data": { }, "meta": null, "message": "تم إنشاء الطلب بنجاح." }
```

- **القوائم المرقّمة:** `meta = { current_page, last_page, per_page, total, from, to }`، ويُحدَّد عدد العناصر بـ `?per_page=` (حتى 100).
- **أخطاء التحقق (422):** تضيف `errors: { field: ["رسالة بالعربية"] }`.
- **أخطاء العمل:** مثل الرصيد غير الكافي أو الكود المستخدم. تضيف `code` لتتعامل معه الواجهة، مثل `insufficient_balance` و`code_used` و`kyc_required` و`player_not_found`.
- **الرسائل كلها بالعربية:** من `lang/ar`.

## نقاط الـ API

**عامة (بدون توكن):**

- `GET /home`
- `GET /categories`
- `GET /categories/{slug}`
- `GET /products/{id}`
- `GET /search?q=`
- `GET /payment-methods`
- `GET /agents`
- `GET /levels`
- `GET /pages/{about|privacy}`
- `GET /settings/public`

**التوثيق:**

- `POST /auth/register`
- `POST /auth/login`: الحقل `login` يقبل اسم المستخدم أو البريد.
- `POST /auth/two-factor`
- `POST /auth/google`: يستقبل `id_token`.
- `POST /auth/logout`
- `POST /auth/forgot-password`
- `POST /auth/reset-password`

**بتوكن (`Authorization: Bearer …`):**

- **الحساب:**
  - `GET /me`
  - `PUT /me/profile`
  - `POST /me/avatar`
  - `PUT /me/password`
  - `PUT /me/two-factor`
  - `POST /me/telegram/link`
- **الطلبات:**
  - `POST /products/{id}/check-name`
  - `POST /orders`: مع الترويسة **`Idempotency-Key`**.
  - `GET /orders?status=&from=&to=&q=`: ‏`status` يقبل أكثر من حالة مفصولة بفواصل، مثل `pending,processing`.
  - `GET /orders/{id}`
- **المحفظة:**
  - `GET /wallet`
  - `GET /wallet/transactions?from=&to=`
  - `POST /recharge-codes/redeem`
- **الإيداعات:**
  - `POST /deposits`: ‏multipart مع `receipt_image`.
  - `GET /deposits?from=&to=`
- **توثيق الهوية:**
  - `POST /kyc`
  - `GET /kyc`
- **المفضلة:**
  - `GET /favorites`
  - `POST /favorites/{product}`
  - `DELETE /favorites/{product}`
- **الإشعارات:**
  - `GET /notifications?from=&to=`: يتضمن `meta.unread_count`.
  - `POST /notifications/read-all`

### الشراء ومنع الخصم المزدوج

ولّد `Idempotency-Key` جديدًا (UUID) عند فتح صفحة الشراء، وأعد استخدامه عند إعادة المحاولة:

```http
POST /api/v1/orders
Authorization: Bearer <token>
Idempotency-Key: 3f0b6c8e-6c5e-4d0e-9a51-9b8f1c2d7e10

{ "product_id": 3, "player_id": "5123456", "player_name": "Player_3456" }
```

- **أول مرة:** يردّ بـ `201`، ويُخصم المبلغ ويُنشأ الطلب.
- **المفتاح نفسه مرة أخرى:** يردّ بـ `200` ويُعيد الطلب نفسه **دون خصم جديد**.

### حدود الطلبات (Rate limiting)

| الحد | المتغير | القيمة الافتراضية |
|---|---|---|
| تسجيل الدخول | `RATE_LIMIT_LOGIN` | 5 في الدقيقة |
| التحقق من اسم اللاعب | `RATE_LIMIT_CHECK_NAME` | 20 في الدقيقة |
| الطلبات والإيداعات والأكواد | `RATE_LIMIT_ORDERS` | 10 في الدقيقة |

---

## قواعد المال

- كل المبالغ `decimal(14,2)`. الحساب يتم بدقة عشرية عبر `App\Support\Money` (مبني على brick/math)، **ولا يُستخدم float أبدًا**.
- **`App\Services\WalletService` هي المكان الوحيد الذي يعدّل `users.balance`:**
  - الدوال: `credit()` و`debit()` و`refund()` و`adjust()`.
  - كل عملية تعمل داخل `DB::transaction` مع `lockForUpdate()` على صف المستخدم، وتسجّل حركة في `wallet_transactions` فيها الرصيد قبل العملية وبعدها.
  - الحقل `balance` غير موجود في `$fillable`.
- **الشراء** (`OrderService::place`): قفل المستخدم ← تحقق من الرصيد ← إنشاء الطلب ← خصم. كل ذلك في معاملة واحدة. خصم المستوى يُحسب على السيرفر فقط.
- **الرفض:** رفض الطلب يعيد المبلغ تلقائيًا، فتصبح الحالة `refunded` وتُسجَّل حركة `credit`. لا يمكن استرداد الطلب نفسه مرتين.
- **الإيداع وأكواد الشحن:**
  - قبول الإيداع يضيف `amount_usd`، ويمكن للمراجع تصحيح المبلغ قبل القبول.
  - كود الشحن يُقفل ويُستخدم مرة واحدة فقط.
  - الأكواد تُخزَّن مشفّرة (HMAC-SHA256) مع آخر 4 أحرف للعرض فقط.
- **ترقية المستوى:** تتم تلقائيًا عند اكتمال الطلب، حسب إجمالي المشتريات المكتملة.

## لوحة التحكم

| الدور | الصلاحيات |
|---|---|
| `super_admin` | كل شيء، بما فيه إدارة المشرفين وأدوارهم |
| `admin` | كل شيء عدا حسابات المشرفين |
| `support` | الطلبات والإيداعات فقط: عرضها وتنفيذها |

**ما في اللوحة:**

- **لوحة التحكم:**
  - مبيعات اليوم والشهر.
  - الطلبات والإيداعات وطلبات KYC المعلّقة.
  - إجمالي أرصدة المستخدمين.
  - رسم بياني للمبيعات في آخر 30 يومًا.
- **المستخدمون:**
  - بحث وحظر.
  - حركات المحفظة والطلبات لكل مستخدم.
  - «تعديل الرصيد» عبر `WalletService` مع سبب إلزامي.
- **الأقسام:** شجرة بالتنقّل بين المستويات، مع رفع صور وترتيب بالسحب.
- **المنتجات:**
  - تعديل سريع للسعر من الجدول.
  - تعديل جماعي للأسعار (نسبة أو مبلغ).
  - تفعيل أو إيقاف.
- **الطلبات:**
  - فلاتر الحالة والتاريخ.
  - إجراءات: تنفيذ (مع كود التسليم)، رفض مع إعادة المبلغ، ملاحظة.
- **الإيداعات:** معاينة الإيصال، وقبول (يضيف الرصيد) أو رفض.
- **توثيق الهوية (KYC):** الصور محفوظة بشكل خاص وتُعرض بروابط مؤقتة، مع قبول أو رفض.
- **طرق الدفع:** صورة وتعليمات وبيانات حساب وسعر صرف وترتيب.
- **أكواد الشحن:** توليد دفعة وتنزيلها CSV فورًا (الأكواد تظهر مرة واحدة فقط)، وتصدير السجل.
- **صفحات أخرى:**
  - المستويات والوكلاء والبانرات.
  - الإعدادات: واتساب، بريد، روابط التواصل، نص «من نحن»، سياسة الخصوصية.
  - سجل حركات المحفظة (للقراءة فقط).

عند تغيّر حالة الطلب أو الإيداع أو التوثيق، يصل للمستخدم إشعار يظهر في `GET /notifications`.

## البيانات الأولية (Seeders)

| البيانات | المحتوى |
|---|---|
| الأقسام | 11 قسمًا رئيسيًا |
| الألعاب | PUBG MOBILE وFREE FIRE وJAWAKER وغيرها |
| الاشتراكات | SHAHID وNETFLIX وOSN + وغيرها |
| شحن ببجي | PUBG MOBILE ← PUBG GLOBAL AUTO، بست باقات (60 UC = 1.01$ … 8100 UC = 91.77$) |
| المستويات | 6 مستويات (البرونزي من 0، الفضي من 500$) |
| طرق الدفع | 29 طريقة: محافظ، عملات رقمية، بنوك، رواتب وكالات، أكواد |
| الوكلاء | 3 وكلاء |
| أخرى | الإعدادات الافتراضية وبانران |

> ⚠️ هذه القيم **مؤقتة** ويجب ضبطها من اللوحة قبل الإطلاق:
> - **أسعار صرف العملات المحلية:** EGP وSYP وIQD وJOD وTRY وغيرها، في «طرق الدفع».
> - **حدود المستويات بعد الفضي.**
>
> لم تُضف أسعار للألعاب الأخرى؛ أضف باقاتها من «المنتجات».

## الاختبارات

اختبارات التزامن تحتاج أقفال صفوف حقيقية (InnoDB)، لذلك تعمل الاختبارات على قاعدة MySQL منفصلة اسمها `alfaore_test`، ببيانات الاتصال نفسها الموجودة في `.env`:

```bash
mysql -u root -p -e "CREATE DATABASE alfaore_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
php artisan test --compact
```

**ما تغطيه:**

- الشراء بالرصيد وخصم المستوى.
- منع الخصم المزدوج (Idempotency).
- إعادة المبلغ عند الرفض.
- قبول الإيداع ورفضه.
- استعمال الكود مرة واحدة فقط.
- **طلبان متزامنان فعليًا** في عمليتي PHP منفصلتين على الرصيد نفسه، والمفتاح نفسه يُرسل بالتوازي.
- كل نقاط الـ API.
- صفحات اللوحة وصلاحيات الأدوار.

## ملاحظات للتطوير لاحقًا

- **مزوّد الشحن:**
  - الواجهة `App\Services\GameProviders\GameProvider`.
  - التطبيق الحالي `FakeGameProvider` للتطوير: أي معرّف رقمي من 5 أرقام فأكثر موجود، والمعرّف الذي ينتهي بـ `000` غير موجود.
  - لإضافة مزوّد حقيقي: اكتب كلاسًا يطبّق الواجهة، ثم سجّله في `AppServiceProvider` وغيّر `GAME_PROVIDER`.
- **التحقق بخطوتين:** يرسل رمزًا من 6 أرقام إلى البريد عند الدخول، فاضبط إعدادات `MAIL_*`.
- **تيليجرام:**
  - `POST /me/telegram/link` يولّد رابط `t.me/<bot>?start=<token>`.
  - ربط `chat_id` بالحساب يحتاج Webhook للبوت، وهو غير مُنفَّذ بعد.
- **Google:** ضع `GOOGLE_CLIENT_ID`، وأرسل `id_token` القادم من Google Sign-In في الواجهة.
