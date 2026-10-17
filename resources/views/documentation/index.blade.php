<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دليل التشغيل والشرح الشامل للنظام | Orcal ERP Documentation</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Tajawal', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            900: '#1e1b4b',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
        }
        @media print {
            .no-print { display: none !important; }
            .print-break { page-break-before: always; }
            body { background: #fff !important; color: #000 !important; }
            .shadow-sm, .shadow-md, .shadow-lg, .shadow-xl { box-shadow: none !important; border: 1px solid #e2e8f0 !important; }
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">

    <!-- Header Navigation -->
    <header class="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-slate-200 shadow-sm no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-500 text-white flex items-center justify-center font-black text-2xl shadow-md shadow-brand-500/20">
                    O
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-900 leading-tight">نظام أوركال الشامل (Orcal ERP)</h1>
                    <p class="text-xs text-slate-500 font-medium">الدليل التشغيلي الشامل، المعمارية المالية، ودليل الاستخدام</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="#scoping-architecture" class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-purple-50 text-purple-700 text-xs font-bold hover:bg-purple-100 transition border border-purple-200">
                    <span>🔐 نطاق الرؤية والصلاحيات</span>
                </a>
                <a href="#financial-flow" class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-indigo-50 text-indigo-700 text-xs font-bold hover:bg-indigo-100 transition border border-indigo-200">
                    <span>🔄 دورة الأموال</span>
                </a>
                <a href="#faq-section" class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-amber-50 text-amber-800 text-xs font-bold hover:bg-amber-100 transition border border-amber-200">
                    <span>💡 الأسئلة الشائعة</span>
                </a>
                <a href="/" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    <span>لوحة التحكم</span>
                </a>
                <button onclick="window.print()" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-100 text-slate-700 text-sm font-medium hover:bg-slate-200 transition border border-slate-200">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span class="hidden sm:inline">طباعة / PDF</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Hero & Search Banner -->
    <div class="bg-gradient-to-b from-indigo-900 via-slate-900 to-slate-900 text-white py-14 px-4 sm:px-6 lg:px-8 no-print relative overflow-hidden">
        <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>
        <div class="max-w-4xl mx-auto text-center relative z-10">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 mb-4 shadow-sm">
                ✨ الدليل المرجعي والشرح الشامل لإدارة العمليات والمالية وشؤون الموظفين
            </span>
            <h2 class="text-3xl sm:text-4xl font-black tracking-tight mb-4 leading-tight">
                كيف يعمل النظام وما هو نطاق الرؤية ودورة الأموال؟
            </h2>
            <p class="text-slate-300 text-base max-w-3xl mx-auto mb-8 leading-relaxed font-normal">
                تم بناء النظام وفق معمارية متقدمة تدعم تعدد المنشآت والشركات (Multi-tenancy)، مع عزل دقيق للبيانات حسب الفروع والمؤسسات، وتكامل آلي لحظي بين حركة الخزينة، الفواتير، مسيرات الرواتب، والأقساط والسلف.
            </p>

            <!-- Search input -->
            <div class="max-w-xl mx-auto relative">
                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input 
                    type="text" 
                    id="resourceSearch" 
                    placeholder="ابحث عن أي قسم أو وظيفة (مثال: الصلاحيات، الخزينة، الرواتب، الأقساط، السلف...)" 
                    class="w-full pr-11 pl-4 py-3.5 rounded-2xl bg-white/10 backdrop-blur border border-white/20 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white/15 transition text-sm font-medium shadow-xl"
                >
            </div>

            <!-- Stats Bar -->
            <div class="flex flex-wrap items-center justify-center gap-6 mt-8 text-xs font-semibold text-slate-300">
                <div class="flex items-center gap-2 bg-white/5 px-3 py-1.5 rounded-xl border border-white/10">
                    <span class="w-2.5 h-2.5 rounded-full bg-purple-400"></span>
                    <span>مستويات العزل: <strong>4 مستويات صلاحية ورؤية</strong></span>
                </div>
                <div class="flex items-center gap-2 bg-white/5 px-3 py-1.5 rounded-xl border border-white/10">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                    <span>الموديولات الأساسية: <strong>{{ $totalModules }} موديولات</strong></span>
                </div>
                <div class="flex items-center gap-2 bg-white/5 px-3 py-1.5 rounded-xl border border-white/10">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-400"></span>
                    <span>الوحدات المشروحة: <strong>{{ $totalResources }} وحدة</strong></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── Scoping & Multi-tenancy Permissions Architecture (Prominent Top Section) ─── -->
    <section id="scoping-architecture" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-4 w-full no-print">
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-purple-200 shadow-sm space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-5">
                <div>
                    <span class="inline-block px-3 py-1 bg-purple-50 text-purple-700 text-xs font-bold rounded-lg border border-purple-100 mb-1.5">
                        🔐 مصفوفة العزل ومستويات الرؤية والصلاحية (Multi-tenancy Architecture)
                    </span>
                    <h3 class="text-xl sm:text-2xl font-black text-slate-900">كيف يتم عزل البيانات وماذا يرى كل مستخدم في النظام؟</h3>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        نظام عزل وتدفق بيانات هرمي ذكي يضمن خصوصية وأمان معلومات كل منشأة وفرع وموظف.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                
                <!-- Level 1: Super Admin -->
                <div class="bg-gradient-to-br from-slate-900 to-indigo-950 text-white rounded-2xl p-5 border border-slate-800 space-y-3.5 shadow-md flex flex-col justify-between">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="w-8 h-8 rounded-xl bg-white/10 text-white flex items-center justify-center font-black text-sm">👑</span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-400/20 text-amber-300 border border-amber-400/30">وصول شامل وغير مقيد</span>
                        </div>
                        <h4 class="text-base font-black text-white">1. مدير النظام الرئيسي (Super Admin)</h4>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            صلاحية مطلقة لإدارة المنظومة بالكامل.
                        </p>
                    </div>
                    <div class="bg-white/5 rounded-xl p-3 border border-white/10 text-xs space-y-1.5">
                        <div class="font-bold text-amber-300">نطاق الرؤية:</div>
                        <p class="text-slate-200 leading-normal">
                            يرى <strong>جميع الشركات، المؤسسات، الفروع، والموظفين، وكافة السجلات المالية والتقارير</strong> بدون أي قيود أو استثناءات.
                        </p>
                    </div>
                </div>

                <!-- Level 2: Owner -->
                <div class="bg-gradient-to-br from-purple-50 to-indigo-50/50 rounded-2xl p-5 border border-purple-200 space-y-3.5 shadow-sm flex flex-col justify-between">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="w-8 h-8 rounded-xl bg-purple-600 text-white flex items-center justify-center font-black text-sm">🏛️</span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">المالك / الشريك</span>
                        </div>
                        <h4 class="text-base font-black text-slate-900">2. أصحاب العمل والملاك (Owner)</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            صاحب المنشآت والسجلات التجارية التابعة له.
                        </p>
                    </div>
                    <div class="bg-white rounded-xl p-3 border border-purple-100 text-xs space-y-1.5">
                        <div class="font-bold text-purple-900">نطاق الرؤية:</div>
                        <p class="text-slate-700 leading-normal">
                            يرى ويتابع <strong>كافة الشركات والمؤسسات المسجلة تحت ملكيته بجميع فروعها</strong>، ويطّلع على تقاريرها وإيراداتها وعملياتها المالية كاملة.
                        </p>
                    </div>
                </div>

                <!-- Level 3: General Manager (Company Admin) -->
                <div class="bg-gradient-to-br from-blue-50 to-cyan-50/50 rounded-2xl p-5 border border-blue-200 space-y-3.5 shadow-sm flex flex-col justify-between">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center font-black text-sm">🏢</span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">إشراف على مستوى الشركة</span>
                        </div>
                        <h4 class="text-base font-black text-slate-900">3. المدير العام للمنشأة (Company Admin)</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            هو الشخص <strong>المرتبط بشركة أو مؤسسة محددة وغير مرتبط بفرع معين</strong>.
                        </p>
                    </div>
                    <div class="bg-white rounded-xl p-3 border border-blue-100 text-xs space-y-1.5">
                        <div class="font-bold text-blue-900">نطاق الرؤية:</div>
                        <p class="text-slate-700 leading-normal">
                            يرى ويشرف على <strong>كافة فروع وموظفي وخزائن وعمليات تلك الشركة بالكامل</strong>، مع حجب وعزل بيانات الشركات والمؤسسات الأخرى عنه.
                        </p>
                    </div>
                </div>

                <!-- Level 4: Branch Staff & Manager -->
                <div class="bg-gradient-to-br from-emerald-50 to-teal-50/50 rounded-2xl p-5 border border-emerald-200 space-y-3.5 shadow-sm flex flex-col justify-between">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-black text-sm">📍</span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">معزول على مستوى الفرع</span>
                        </div>
                        <h4 class="text-base font-black text-slate-900">4. الموظف / مدير الفرع (Branch Staff)</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            هو الشخص <strong>المرتبط بفرع جغرافي محدد</strong> داخل المؤسسة.
                        </p>
                    </div>
                    <div class="bg-white rounded-xl p-3 border border-emerald-100 text-xs space-y-1.5">
                        <div class="font-bold text-emerald-900">نطاق الرؤية:</div>
                        <p class="text-slate-700 leading-normal">
                            يرى <strong>فقط البيانات والعمليات التابعة لفرعه المحدد حصرياً</strong>، وذلك بدقة بحسب <strong>الصلاحيات الممنوحة لدوره الوظيفي</strong> في مصفوفة الصلاحيات.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ─── Financial Flow & System Architecture Visual Section ─── -->
    <section id="financial-flow" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 pb-6 w-full no-print">
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-sm space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-5">
                <div>
                    <span class="inline-block px-3 py-1 bg-brand-50 text-brand-700 text-xs font-bold rounded-lg border border-brand-100 mb-1.5">
                        🔄 المعمارية المالية
                    </span>
                    <h3 class="text-xl sm:text-2xl font-black text-slate-900">أين تسمع حركة الفلوس وكيف تترابط حسابات الشركة؟</h3>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">توضيح مبسط للمسار المحاسبي والتدفق النقدي داخل المنظومة من لحظة التحصيل حتى الصرف وإقفال الحسابات.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <!-- Box 1: Inflows -->
                <div class="bg-gradient-to-br from-emerald-50/70 to-emerald-100/30 rounded-2xl p-5 border border-emerald-200/80 space-y-3">
                    <div class="flex items-center gap-2 font-bold text-emerald-800 text-sm">
                        <span class="w-7 h-7 rounded-lg bg-emerald-500 text-white flex items-center justify-center font-black">📥</span>
                        <span>1. مصادر الإيرادات والتحصيل</span>
                    </div>
                    <ul class="text-xs text-slate-700 space-y-2 font-medium">
                        <li class="flex items-start gap-1.5">
                            <span class="text-emerald-600 font-bold">•</span>
                            <span><strong>الفواتير المحصلة:</strong> تدخل مباشرة إما لحساب الخزينة (كاش) أو الحساب البنكي.</span>
                        </li>
                        <li class="flex items-start gap-1.5">
                            <span class="text-emerald-600 font-bold">•</span>
                            <span><strong>نقاط البيع POS:</strong> تسحب إلكترونياً (مدى) وتصب في الحساب البنكي (IBAN) للمؤسسة.</span>
                        </li>
                        <li class="flex items-start gap-1.5">
                            <span class="text-emerald-600 font-bold">•</span>
                            <span><strong>تسديد أقساط وسلف الموظفين:</strong> عند السداد كاش، يصدر سند قبض يزيد رصيد الخزينة ويخصم الدين.</span>
                        </li>
                        <li class="flex items-start gap-1.5">
                            <span class="text-emerald-600 font-bold">•</span>
                            <span><strong>مبيعات القيود اليومية:</strong> تسجل صافي التدفق اليومي للفرع.</span>
                        </li>
                    </ul>
                </div>

                <!-- Box 2: Treasury & Center -->
                <div class="bg-gradient-to-br from-indigo-50/70 to-indigo-100/30 rounded-2xl p-5 border border-indigo-200/80 space-y-3">
                    <div class="flex items-center gap-2 font-bold text-indigo-900 text-sm">
                        <span class="w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-black">🏛️</span>
                        <span>2. مراكز وخزائن السيولة</span>
                    </div>
                    <ul class="text-xs text-slate-700 space-y-2 font-medium">
                        <li class="flex items-start gap-1.5">
                            <span class="text-indigo-600 font-bold">•</span>
                            <span><strong>خزينة الشركة النقدية:</strong> تحتفظ بالرصيد التراكمي الجاري، وتتحدث آلياً مع كل سند صرف وقبض.</span>
                        </li>
                        <li class="flex items-start gap-1.5">
                            <span class="text-indigo-600 font-bold">•</span>
                            <span><strong>الحسابات البنكية للمؤسسات:</strong> توثق في نقاط البيع وتستخدم للتحويلات الرسمية للرواتب.</span>
                        </li>
                        <li class="flex items-start gap-1.5">
                            <span class="text-indigo-600 font-bold">•</span>
                            <span><strong>الطباعة والسندات:</strong> كل حركة تثبت بسند مالي رسمي مرقم ومفقط باللغة العربية جاهز للختم.</span>
                        </li>
                    </ul>
                </div>

                <!-- Box 3: Outflows -->
                <div class="bg-gradient-to-br from-rose-50/70 to-rose-100/30 rounded-2xl p-5 border border-rose-200/80 space-y-3">
                    <div class="flex items-center gap-2 font-bold text-rose-900 text-sm">
                        <span class="w-7 h-7 rounded-lg bg-rose-500 text-white flex items-center justify-center font-black">📤</span>
                        <span>3. المصروفات والالتزامات</span>
                    </div>
                    <ul class="text-xs text-slate-700 space-y-2 font-medium">
                        <li class="flex items-start gap-1.5">
                            <span class="text-rose-600 font-bold">•</span>
                            <span><strong>مسيرات الرواتب:</strong> تحسب الاستحقاق بدقة وتخصم السلف تلقائياً قبل الصرف.</span>
                        </li>
                        <li class="flex items-start gap-1.5">
                            <span class="text-rose-600 font-bold">•</span>
                            <span><strong>مكافآت العمل الإضافي:</strong> تحتسب بالساعات والدقائق وتصرف بسند صرف خزينة أو تضاف لمسير الراتب.</span>
                        </li>
                        <li class="flex items-start gap-1.5">
                            <span class="text-rose-600 font-bold">•</span>
                            <span><strong>منح السلف والأقساط:</strong> صرف السلفة يقلل الخزينة ويفتح ذمة مدينة للقسط على الموظف.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content Layout -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1 w-full flex flex-col lg:flex-row gap-8">
        
        <!-- Sidebar Navigation (Desktop) -->
        <aside class="w-full lg:w-64 shrink-0 no-print">
            <div class="sticky top-28 space-y-4">
                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 px-2">فهرس الأقسام</h3>
                    <nav class="space-y-1">
                        @foreach($modules as $key => $module)
                            <a href="#module-{{ $key }}" class="group flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:text-brand-600 hover:bg-brand-50/70 transition">
                                <span>{{ $module['title'] }}</span>
                                <span class="text-xs font-bold text-slate-400 group-hover:text-brand-600 bg-slate-100 group-hover:bg-brand-100 px-2 py-0.5 rounded-full">
                                    {{ count($module['resources']) }}
                                </span>
                            </a>
                        @endforeach
                    </nav>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2 px-2">روابط سريعة</h3>
                    <div class="space-y-1 text-xs font-semibold">
                        <a href="#scoping-architecture" class="block px-3 py-2 rounded-xl text-purple-700 bg-purple-50/70 hover:bg-purple-100 transition">🔐 نطاق الرؤية والصلاحيات</a>
                        <a href="#financial-flow" class="block px-3 py-2 rounded-xl text-indigo-700 bg-indigo-50/70 hover:bg-indigo-100 transition">📊 معمارية تدفق الأموال</a>
                        <a href="#faq-section" class="block px-3 py-2 rounded-xl text-amber-800 bg-amber-50/70 hover:bg-amber-100 transition">💡 دليل إجابات العميل (FAQ)</a>
                        <a href="/settings" target="_blank" class="block px-3 py-2 rounded-xl text-slate-700 bg-slate-50 hover:bg-slate-100 transition">⚙️ إعدادات وهوية النظام</a>
                    </div>
                </div>

                <!-- Info Alert -->
                <div class="bg-gradient-to-br from-brand-50 to-indigo-50/50 rounded-2xl p-4 border border-brand-100 text-xs text-slate-700 leading-relaxed space-y-2">
                    <div class="font-bold text-brand-700 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>قاعدة العزل (Scoping):</span>
                    </div>
                    <p>
                        الموظف المرتبط بفرع يرى فقط داتا فرعه بحسب صلاحياته، بينما المدير المرتبط بشركة يرى كامل فروع شركته، والمالك والسوبر أدمن يشاهدون نطاقهم الشامل.
                    </p>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 space-y-12">
            @foreach($modules as $moduleKey => $module)
                <section id="module-{{ $moduleKey }}" class="module-group space-y-6">
                    
                    <!-- Module Header -->
                    <div class="border-b border-slate-200 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="p-2 rounded-xl bg-slate-900 text-white">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            </span>
                            <div>
                                <h3 class="text-xl font-bold text-slate-900">{{ $module['title'] }}</h3>
                                <p class="text-sm text-slate-500 font-medium">{{ $module['description'] }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Resources Cards -->
                    <div class="grid grid-cols-1 gap-6">
                        @foreach($module['resources'] as $res)
                            <div class="resource-card bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden transition hover:shadow-md" data-name="{{ strtolower($res['name'] . ' ' . $res['purpose'] . ' ' . implode(' ', $res['features'])) }}">
                                
                                <!-- Card Header -->
                                <div class="bg-slate-50/80 px-6 py-4 border-b border-slate-200/70 flex flex-wrap items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <h4 class="text-base font-bold text-slate-900">{{ $res['name'] }}</h4>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-{{ $res['badge_color'] }}-50 text-{{ $res['badge_color'] }}-700 border border-{{ $res['badge_color'] }}-200">
                                            {{ $res['badge'] }}
                                        </span>
                                    </div>
                                    <a href="{{ $res['filament_url'] }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white text-slate-700 text-xs font-semibold hover:bg-brand-50 hover:text-brand-600 hover:border-brand-200 transition border border-slate-200 shadow-2xs">
                                        <span>فتح الشاشة في اللوحة</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                </div>

                                <!-- Card Body -->
                                <div class="p-6 space-y-5">
                                    
                                    <!-- Purpose / Overview -->
                                    <div>
                                        <h5 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">الهدف والغرض الوظيفي</h5>
                                        <p class="text-sm font-medium text-slate-700 leading-relaxed">{{ $res['purpose'] }}</p>
                                    </div>

                                    <!-- Key Features -->
                                    <div>
                                        <h5 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">أهم الوظائف والمميزات</h5>
                                        <ul class="space-y-1.5">
                                            @foreach($res['features'] as $feat)
                                                <li class="flex items-start gap-2 text-sm text-slate-600">
                                                    <span class="text-emerald-500 font-bold">✓</span>
                                                    <span>{{ $feat }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>

                                    <!-- Relationships -->
                                    <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100 text-xs text-slate-600 flex items-start gap-2">
                                        <span class="font-bold text-slate-700 shrink-0">🔗 الترابط والعلاقات:</span>
                                        <span>{{ $res['relationships'] }}</span>
                                    </div>

                                    <!-- Scoping & Permissions -->
                                    <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-100 text-slate-500 bg-slate-50/50 p-3 rounded-xl">
                                        <span class="flex items-start sm:items-center gap-1.5">
                                            <svg class="w-4 h-4 text-purple-600 shrink-0 mt-0.5 sm:mt-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            <span><strong class="text-slate-700">نطاق الرؤية والصلاحية:</strong> {{ $res['scope'] }}</span>
                                        </span>
                                    </div>

                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach

            <!-- ─── Comprehensive Client FAQ Section ─── -->
            <section id="faq-section" class="pt-6 space-y-6">
                <div class="border-b border-slate-200 pb-4">
                    <div class="flex items-center gap-3">
                        <span class="p-2 rounded-xl bg-amber-500 text-white">💡</span>
                        <div>
                            <h3 class="text-2xl font-black text-slate-900">دليل الأسئلة الشائعة وإجاباتها (FAQ)</h3>
                            <p class="text-sm text-slate-500 font-medium">أهم الاستفسارات التشغيلية والمحاسبية وإجاباتها النموذجية والعملية.</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-2">
                        <h4 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span class="text-brand-600 font-black">س 1:</span>
                            <span>من يرى ماذا في النظام؟ وكيف يتم عزل بيانات الشركات والفروع؟</span>
                        </h4>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            <strong>الجواب:</strong> النظام مبني على 4 مستويات محددة بدقة:
                            <br>• <strong>السوبر أدمن (Super Admin):</strong> يرى كل المنشآت والفروع والسجلات بدون أي قيود.
                            <br>• <strong>المالك (Owner):</strong> يرى ويتابع كافة الشركات والمؤسسات التابعة له بجميع فروعها.
                            <br>• <strong>المدير العام للمنشأة (Company Admin):</strong> شخص مرتبط بمؤسسة محددة (غير مقيد بفرع) -> يرى كافة فروع وعمليات شركته.
                            <br>• <strong>الموظف أو مدير الفرع:</strong> مرتبط بفرع معين -> يرى فقط الداتا التابعة لفرعه وبناءً على الصلاحيات الممنوحة لدوره الوظيفي.
                        </p>
                    </div>

                    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-2">
                        <h4 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span class="text-brand-600 font-black">س 2:</span>
                            <span>أين تسمع حركة الفلوس في النظام ومن أين أعرف رصيد الشركة الفعلي؟</span>
                        </h4>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            <strong>الجواب:</strong> تسمع كل حركة نقدية في جدول <strong>حركات الخزينة وسندات القبض والصرف</strong>؛ حيث يتم حساب الرصيد التراكمي (<code class="text-indigo-600">balance_after</code>) تلقائياً بعد كل عملية. كما تم تزويد لوحة التحكم الرئيسية (Dashboard) بويدجت مالي ذكي يعرض رصيد الخزينة، إجمالي المبيعات، ومسيرات الرواتب لحظة بلحظة.
                        </p>
                    </div>

                    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-2">
                        <h4 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span class="text-brand-600 font-black">س 3:</span>
                            <span>كيف يتعامل النظام مع سداد الأقساط والسلف للموظفين؟</span>
                        </h4>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            <strong>الجواب:</strong> عند إنشاء قسط جديد للموظف، يتم حساب القسط الشهري وجدولته. وعند السداد، يتيح النظام خيارين: <strong>(1) السداد نقداً كاش:</strong> ويدخل المبلغ في الخزينة لزيادة النقدية ويخصم من دين الموظف، أو <strong>(2) الخصم من الراتب:</strong> ويتم خصم القسط آلياً في مسير الراتب الشهري للموظف، وتتحول حالة القسط تلقائياً إلى "مكتمل المسدد" عند وصول المتبقي للصفر.
                        </p>
                    </div>

                    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-2">
                        <h4 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span class="text-brand-600 font-black">س 4:</span>
                            <span>كيف يحسب النظام مكافآت العمل الإضافي بالساعات والدقائق؟</span>
                        </h4>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            <strong>الجواب:</strong> بمجرد اختيار الموظف في شاشة <strong>سجل مكافآت الموظفين</strong>، يجلب النظام أجر الساعة التلقائي المحسوب من راتبه الأساسي، ويطبق المعادلة المعتمدة: <code class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded font-bold">(أجر الساعة × عدد الساعات) + (أجر الدقيقة × عدد الدقائق) = صافي المكافأة</code>، وتمر المكافأة بدورة اعتماد واضحة قبل الصرف.
                        </p>
                    </div>

                    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-2">
                        <h4 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span class="text-brand-600 font-black">س 5:</span>
                            <span>كيف أقوم بتغيير اسم النظام والعلامة التجارية أو تخصيص الإعدادات؟</span>
                        </h4>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            <strong>الجواب:</strong> من خلال الدخول على شاشة <strong>إعدادات النظام</strong>، وتعديل قيمة المفتاح <code class="text-slate-900 bg-slate-100 px-2 py-0.5 rounded font-bold">site_name</code>؛ حيث يرتبط اسم اللوحة ديناميكياً بهذا الإعداد، وإذا تم تعطيله يعود النظام تلقائياً للاسم الافتراضي "مجموعة فواز القابضة".
                        </p>
                    </div>
                </div>
            </section>

            <!-- Empty Search State -->
            <div id="noResults" class="hidden text-center py-16 bg-white rounded-2xl border border-slate-200 p-8 space-y-3">
                <div class="text-4xl">🔍</div>
                <h4 class="text-lg font-bold text-slate-800">لا توجد نتائج مطابقة لبحثك</h4>
                <p class="text-sm text-slate-500">جرب البحث بكلمة أخرى مثل "الصلاحيات"، "الخزينة"، "الرواتب"، "الأقساط"، أو "الموظفين".</p>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-6 mt-12 text-center text-xs text-slate-500 no-print">
        <p>نظام أوركال لإدارة العمليات والموارد (Orcal ERP) — الدليل الشامل والمحدث</p>
    </footer>

    <!-- Client-side Realtime Search Script -->
    <script>
        const searchInput = document.getElementById('resourceSearch');
        const cards = document.querySelectorAll('.resource-card');
        const modules = document.querySelectorAll('.module-group');
        const noResults = document.getElementById('noResults');

        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.trim().toLowerCase();
            let totalVisible = 0;

            cards.forEach(card => {
                const text = card.getAttribute('data-name');
                if (!query || text.includes(query)) {
                    card.style.display = '';
                    totalVisible++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Hide/Show module headers if all their cards are hidden
            modules.forEach(mod => {
                const visibleInMod = mod.querySelectorAll('.resource-card:not([style*="display: none"])');
                if (visibleInMod.length === 0) {
                    mod.style.display = 'none';
                } else {
                    mod.style.display = '';
                }
            });

            if (totalVisible === 0) {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
