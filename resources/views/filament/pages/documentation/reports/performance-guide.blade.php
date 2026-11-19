{{-- 
    دليل وشرح لوحة ومؤشرات الأداء الشاملة
    يشمل: أداء الموظف الشخصي + تقييم أداء المشرفين بالمناوبات + لوحة أداء المنشآت والأقسام العامة
    فيديو الشرح: performance.mp4
--}}

<div class="carnet-perf-guide-wrapper">

    <!-- 1. شريط الأدوات العلوي على نمط كارنت -->
    <div class="carnet-top-bar" style="border-right: 4px solid #4f46e5;">
        <div class="carnet-title-box">
            <div class="carnet-logo-icon" style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); box-shadow: 0 4px 12px rgba(79, 70, 229, 0.28);">
                <span style="font-size: 22px;">🎯</span>
            </div>
            <div class="carnet-title-text">
                <h3>{{ __('documentation.performance_guide.card_title') }}</h3>
                <p>{{ __('documentation.performance_guide.hub_note') }}</p>
            </div>
        </div>

        <div class="carnet-actions">
            <!-- حقل البحث الحي المباشر -->
            <div class="carnet-search-box" style="min-width: 280px;">
                <span class="carnet-search-icon">🔍</span>
                <input
                    type="text"
                    id="carnet-performance-search"
                    class="carnet-search-input"
                    placeholder="ابحث في دليل الأداء (مثال: نقاط، خصم، مشرف، صدارة، كشف)..."
                    onkeyup="filterPerformanceContent()"
                >
            </div>

            <button type="button" onclick="copyPerformanceShareLink()" class="carnet-btn carnet-btn-outline" title="نسخ رابط صفحة الشرح والفيديو">
                <span>🔗</span>
                <span>مشاركة الرابط</span>
            </button>

            <button type="button" onclick="window.print()" class="carnet-btn carnet-btn-outline" title="طباعة الشرح أو حفظه كـ PDF">
                <span>🖨️</span>
                <span>طباعة الدليل</span>
            </button>

            <a href="/company-department-performance" class="carnet-btn carnet-btn-primary" style="background: #4f46e5; border-color: #4f46e5;">
                <span>{{ __('documentation.performance_guide.open_company_perf') }}</span>
                <span>⇦</span>
            </a>

            <a href="/supervisor-performance" class="carnet-btn carnet-btn-secondary" style="color: #4f46e5; border-color: #c7d2fe;">
                <span>{{ __('documentation.performance_guide.open_supervisor_perf') }}</span>
                <span>⇦</span>
            </a>

            <a href="/my-tasks" class="carnet-btn carnet-btn-outline" title="مهام وأداء الموظف الشخصية">
                <span>{{ __('documentation.performance_guide.open_my_tasks') }}</span>
                <span>⇦</span>
            </a>
        </div>
    </div>

    <!-- 2. شريط التبويبات الثانوية الأفقي -->
    <nav class="carnet-tabs-nav carnet-perf-subtabs" style="margin-top: 14px; margin-bottom: 20px;">
        <a href="#sec-video" class="carnet-tab-link active">
            <span>🎬</span>
            <span>فيديو الشرح</span>
        </a>
        <a href="#sec-company-dept" class="carnet-tab-link">
            <span>🏢</span>
            <span>لوحة المنشآت والأقسام العامة</span>
        </a>
        <a href="#sec-employee-perf" class="carnet-tab-link">
            <span>🧑‍💼</span>
            <span>صفحة أداء الموظف (لوحتي)</span>
        </a>
        <a href="#sec-supervisor-eval" class="carnet-tab-link">
            <span>👨‍💼</span>
            <span>تقييم المشرفين (معادلة 50/50)</span>
        </a>
        <a href="#sec-score-rules" class="carnet-tab-link">
            <span>📐</span>
            <span>معادلة المقياس والخصومات (0-100)</span>
        </a>
        <a href="#sec-escalations" class="carnet-tab-link">
            <span>🚨</span>
            <span>تصعيدات الأداء وعتبة 65</span>
        </a>
        <a href="#sec-five-tabs" class="carnet-tab-link">
            <span>📊</span>
            <span>التبويبات الخمسة للوحة</span>
        </a>
        <a href="#sec-faq" class="carnet-tab-link">
            <span>❓</span>
            <span>الأسئلة الشائعة والقواعد</span>
        </a>
    </nav>

    <!-- 3. المحتوى والفهرس الجانبي (عمودان) -->
    <div class="carnet-content-grid">

        <!-- الفهرس الجانبي الثابت -->
        <aside class="carnet-index-card">
            <div class="carnet-index-heading" style="color: #4f46e5;">
                <span>📑</span>
                <span>فهرس دليل منظومة الأداء</span>
            </div>
            <ul class="carnet-index-list">
                <li class="carnet-index-item">
                    <a href="#sec-video">
                        <span class="icon">🎬</span>
                        <span>فيديو الشرح التفاعلي</span>
                    </a>
                </li>
                <li class="carnet-index-item">
                    <a href="#sec-company-dept">
                        <span class="icon">🏢</span>
                        <span>لوحة أداء المنشآت والأقسام</span>
                    </a>
                </li>
                <li class="carnet-index-item">
                    <a href="#sec-employee-perf">
                        <span class="icon">🧑‍💼</span>
                        <span>صفحة أداء الموظف الشخصية</span>
                    </a>
                </li>
                <li class="carnet-index-item">
                    <a href="#sec-supervisor-eval">
                        <span class="icon">👨‍💼</span>
                        <span>تقييم أداء المشرفين بالمناوبات</span>
                    </a>
                </li>
                <li class="carnet-index-item">
                    <a href="#sec-score-rules">
                        <span class="icon">📐</span>
                        <span>معادلة احتساب الدرجة واستردادها</span>
                    </a>
                </li>
                <li class="carnet-index-item">
                    <a href="#sec-escalations">
                        <span class="icon">🚨</span>
                        <span>نظام التصعيد الآلي للإنذار المبكر</span>
                    </a>
                </li>
                <li class="carnet-index-item">
                    <a href="#sec-five-tabs">
                        <span class="icon">📊</span>
                        <span>الأبعاد الخمسة (تشغيل، مالية، التزام)</span>
                    </a>
                </li>
                <li class="carnet-index-item">
                    <a href="#sec-faq">
                        <span class="icon">❓</span>
                        <span>الأسئلة الشائعة والإرشادات</span>
                    </a>
                </li>
            </ul>

            <div style="margin-top: 18px; padding: 12px; border-radius: 10px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 12px; line-height: 1.6; color: #64748b;" class="dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
                <strong style="color: #4f46e5; display: block; margin-bottom: 4px;">⚡ ربط مباشر وموحد:</strong>
                جميع مؤشرات الأداء تُحسب لحظياً من واقع جداول العمليات والتكليفات الفعلية في النظام دون أي إدخال يدوي مكرر.
            </div>
        </aside>

        <!-- المحتوى المتدفق الرئيسي (يسار) -->
        <main class="carnet-main-flow">

            <!-- ==========================================================================
                 القسم: فيديو شرح منظومة الأداء
                 ========================================================================== -->
            <section id="sec-video" class="carnet-section-card carnet-searchable-perf" style="padding: 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                <div class="carnet-video-head" style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                    <span class="carnet-play-badge" style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 14px; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.35);">▶</span>
                    <h2 style="font-size: 19px; font-weight: 800; color: #0f172a; margin: 0;" class="dark:text-white">{{ __('documentation.performance_guide.video_title') }}</h2>
                </div>
                <p class="carnet-video-subtitle" style="font-size: 13.5px; color: #64748b; margin: 0 0 16px 0;" class="dark:text-slate-400">
                    {{ __('documentation.performance_guide.video_desc') }}
                </p>

                <!-- مشغل الفيديو المتكامل والداعم لكافة مسارات السيرفر المحتملة -->
                <div class="carnet-video-box" style="background: #000000; border-radius: 14px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.22); border: 1px solid #334155;">
                    <video controls preload="metadata" class="carnet-video-element" id="playerPerformance" style="width: 100%; max-height: 520px; display: block; background: #000000; outline: none;">
                        <source src="{{ asset('vidoes/performance.mp4') }}" type="video/mp4">
                        <source src="/vidoes/performance.mp4" type="video/mp4">
                        <source src="{{ asset('videos/performance.mp4') }}" type="video/mp4">
                        <source src="/videos/performance.mp4" type="video/mp4">
                        <source src="{{ asset('vidoes/performance') }}" type="video/mp4">
                        <source src="/vidoes/performance" type="video/mp4">
                        <source src="{{ asset('videos/performance') }}" type="video/mp4">
                        <source src="/videos/performance" type="video/mp4">
                        <source src="{{ asset('vidoes/Performance.mp4') }}" type="video/mp4">
                        <source src="/vidoes/Performance.mp4" type="video/mp4">
                        <source src="{{ asset('videos/Performance.mp4') }}" type="video/mp4">
                        <source src="/videos/Performance.mp4" type="video/mp4">
                        متصفحك لا يدعم تشغيل الفيديو المباشر، يرجى استخدام متصفح حديث.
                    </video>
                    <div class="carnet-video-caption" style="padding: 12px 18px; background: #0f172a; color: #94a3b8; font-size: 12.5px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; border-top: 1px solid #1e293b;">
                        <span>📹 فيديو توضيحي مسجل من واقع شاشات النظام (أداء الموظف + تقييم المشرف + لوحة الأداء العامة)</span>
                        <span>مسار ملف الفيديو المطلوب رفعه على الخادم: <code style="background: #1e293b; color: #38bdf8; padding: 2px 8px; border-radius: 4px; font-family: monospace; direction: ltr; display: inline-block;">/videos/performance.mp4</code></span>
                    </div>
                </div>

                <!-- شارات توضيح محتويات الفيديو -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-top: 16px;">
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; display: flex; align-items: center; gap: 10px;" class="dark:bg-slate-800 dark:border-slate-700">
                        <span style="font-size: 20px;">🧑‍💼</span>
                        <div>
                            <strong style="font-size: 13px; color: #0f172a; display: block;" class="dark:text-white">المحور الأول: أداء الموظف</strong>
                            <span style="font-size: 11.5px; color: #64748b;" class="dark:text-slate-400">لوحتي، إنجاز التكليفات في الموعد، ونواقص الخدمات</span>
                        </div>
                    </div>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; display: flex; align-items: center; gap: 10px;" class="dark:bg-slate-800 dark:border-slate-700">
                        <span style="font-size: 20px;">👨‍💼</span>
                        <div>
                            <strong style="font-size: 13px; color: #0f172a; display: block;" class="dark:text-white">المحور الثاني: تقييم المشرف</strong>
                            <span style="font-size: 11.5px; color: #64748b;" class="dark:text-slate-400">معادلة 50% أداء الفريق + 50% إنجاز المشرف الشخصي</span>
                        </div>
                    </div>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; display: flex; align-items: center; gap: 10px;" class="dark:bg-slate-800 dark:border-slate-700">
                        <span style="font-size: 20px;">🏢</span>
                        <div>
                            <strong style="font-size: 13px; color: #0f172a; display: block;" class="dark:text-white">المحور الثالث: لوحة الأداء العامة</strong>
                            <span style="font-size: 11.5px; color: #64748b;" class="dark:text-slate-400">درجة المنشأة، مسار صدارة الأقسام، والأبعاد الخمسة</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ==========================================================================
                 القسم 1: لوحة أداء المنشآت والأقسام العامة
                 ========================================================================== -->
            <section id="sec-company-dept" class="carnet-card carnet-searchable-perf" style="padding: 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px;">
                <div class="carnet-card-title-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                    <span class="carnet-card-icon-badge" style="width: 34px; height: 34px; border-radius: 8px; background: rgba(79, 70, 229, 0.12); color: #4f46e5; display: flex; align-items: center; justify-content: center; font-size: 18px;">🏢</span>
                    <h2 class="carnet-card-title" style="font-size: 18px; font-weight: 800; margin: 0; color: #0f172a;" class="dark:text-white">لوحة أداء المنشآت والأقسام العامة</h2>
                </div>

                <p class="carnet-card-text" style="font-size: 14.5px; line-height: 1.85; color: #334155;" class="dark:text-slate-300">
                    تمثل هذه الشاشة غرفة القيادة المركزية للإدارة العليا والملاك والمشرفين. تجمع أداء المنشأة في درجة ذكية واحدة من 100، وتعرض صدارة الأقسام وترتيبها، مع خمسة أبعاد تشغيلية ومالية وتنظيمية شاملة.
                </p>

                <!-- بطاقات شرح العناصر الأساسية في اللوحة -->
                <div style="display: flex; flex-direction: column; gap: 16px; margin-top: 18px;">

                    <!-- 1. البطاقة البنفسجية الرئيسية للدرجة -->
                    <div style="background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%); border: 1px solid #c7d2fe; border-radius: 14px; padding: 18px;" class="dark:bg-slate-900 dark:border-indigo-900/50">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <span style="font-size: 18px;">👑</span>
                            <h3 style="font-size: 15px; font-weight: 800; color: #312e81; margin: 0;" class="dark:text-indigo-300">البطاقة البنفسجية للدرجة التشغيلية (بطاقة التقييم الكبرى)</h3>
                        </div>
                        <p style="font-size: 13.5px; line-height: 1.8; color: #3730a3; margin: 0;" class="dark:text-indigo-200">
                            تعتلي مستوى الأقسام وتعرض اسم المنشأة المفتوحة ودرجتها التشغيلية من 100 عبر حلقة قياس دائرية واحدة واضحة. تضم البطاقة:
                            <br>• <strong>كشف خصومات المهام المتأخرة:</strong> زر وسطي يفتح قائمة المهام التي تسببت في إنقاص الدرجة.
                            <br>• <strong>كشف خصومات نواقص الخدمات:</strong> زر وسطي يفتح معاملات الخدمات المتأخرة المستحقة.
                            <br>• <strong>عتبة مراجعة الإدارة (أقل من 65):</strong> تنبيه تلقائي يوضح ضرورة التدخل الإداري العاجل عند هبوط الدرجة دون 65 نقطة.
                            <br>• <strong>شرح الدرجة التفاعلي:</strong> زر منسدل يوضح بالتفصيل خطوات احتساب النقاط واستعادتها.
                        </p>
                    </div>

                    <!-- 2. مسار صدارة الأقسام التنافسي -->
                    <div style="background: linear-gradient(135deg, #f8fafc 0%, #fefce8 100%); border: 1px solid #fef08a; border-radius: 14px; padding: 18px;" class="dark:bg-slate-900 dark:border-amber-900/50">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <span style="font-size: 18px;">🏆</span>
                            <h3 style="font-size: 15px; font-weight: 800; color: #854d0e; margin: 0;" class="dark:text-amber-300">مسار صدارة الأقسام وتنافس الفرق</h3>
                        </div>
                        <p style="font-size: 13.5px; line-height: 1.8; color: #713f12; margin: 0;" class="dark:text-amber-200">
                            مسار تنافسي سريع يُبرز أفضل 5 أقسام مؤهلة داخل المنشأة مرتبة تنازلياً حسب درجاتها التشغيلية:
                            <br>• يتوج المركز الأول بتاج الصدارة الذهبي 👑 ولقب «المتصدر» مع إبراز فرق النقاط عن أقرب المنافسين.
                            <br>• شرائط تقدم بصرية سريعة مصممة بتنسيق انسيابي ومسرّعة عتادياً دون أي بطء أو استهلاك إضافي لموارد الخادم.
                            <br>• زر انتقال فوري مباشر بضغطة زر واحدة ينقلك من بطاقة الصدارة إلى قائمة موظفي القسم مباشرة.
                        </p>
                    </div>

                    <!-- 3. أوضاع الترتيب الثلاثة للأقسام -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px;" class="dark:bg-slate-900 dark:border-slate-800">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                            <span style="font-size: 18px;">🎛️</span>
                            <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0;" class="dark:text-white">أوضاع ترتيب الأقسام داخل المنشأة (3 أوضاع رئيسية)</h3>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                            <div style="background: #ffffff; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1;" class="dark:bg-slate-800 dark:border-slate-700">
                                <strong style="color: #4f46e5; font-size: 13px; display: block; margin-bottom: 4px;">1. وضع «الكل»:</strong>
                                <span style="font-size: 12.5px; color: #64748b;" class="dark:text-slate-400">الترتيب الافتراضي للخدمات والأقسام حسب بنية المنشأة المعتمدة.</span>
                            </div>
                            <div style="background: #ffffff; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1;" class="dark:bg-slate-800 dark:border-slate-700">
                                <strong style="color: #16a34a; font-size: 13px; display: block; margin-bottom: 4px;">2. «الأفضل أولاً»:</strong>
                                <span style="font-size: 12.5px; color: #64748b;" class="dark:text-slate-400">فرز تنازلي حسب الدرجة التشغيلية، مع إبقاء الأقسام غير المؤهلة بالأسفل.</span>
                            </div>
                            <div style="background: #ffffff; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1;" class="dark:bg-slate-800 dark:border-slate-700">
                                <strong style="color: #dc2626; font-size: 13px; display: block; margin-bottom: 4px;">3. «القسم الأسوأ»:</strong>
                                <span style="font-size: 12.5px; color: #64748b;" class="dark:text-slate-400">يظهر الأقسام الأقل أداءً للتدخل الفوري ومعالجة أسباب التأخير.</span>
                            </div>
                        </div>
                    </div>

                    <!-- 4. الكشوف الوسطية المرقمة - قاعدة المعمارية الصارمة 3.7 -->
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 14px; padding: 18px;" class="dark:bg-slate-900 dark:border-emerald-900/50">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <span style="font-size: 18px;">🛡️</span>
                            <h3 style="font-size: 15px; font-weight: 800; color: #166534; margin: 0;" class="dark:text-emerald-300">الكشوف الوسطية المرقمة (النوافذ المنبثقة المستقلة)</h3>
                        </div>
                        <p style="font-size: 13.5px; line-height: 1.8; color: #14532d; margin: 0;" class="dark:text-emerald-200">
                            وفقاً لمعايير نظام أوركال المعتمدة (البند 3.7)، <strong>تُمنع القوائم الجانبية الساحبة تماماً</strong> في جميع شاشات الأداء والتقارير. كل تفصيل أو كشف (كشف الخدمات، كشف التكليفات، كشوفات المالية والحضور) يفتح حصراً عبر <strong>نوافذ وسطية منبثقة ومرقمة</strong> سهلة القراءة وتدعم التصفح السلس باللمس ولوحة المفاتيح.
                        </p>
                    </div>

                </div>
            </section>

            <!-- ==========================================================================
                 القسم 2: صفحة أداء الموظف الشخصية (لوحتي وملف الموظف)
                 ========================================================================== -->
            <section id="sec-employee-perf" class="carnet-card carnet-searchable-perf" style="padding: 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px;">
                <div class="carnet-card-title-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                    <span class="carnet-card-icon-badge" style="width: 34px; height: 34px; border-radius: 8px; background: rgba(14, 165, 233, 0.12); color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 18px;">🧑‍💼</span>
                    <h2 class="carnet-card-title" style="font-size: 18px; font-weight: 800; margin: 0; color: #0f172a;" class="dark:text-white">صفحة أداء الموظف الشخصية (شاشة لوحتي وملف الموظف)</h2>
                </div>

                <p class="carnet-card-text" style="font-size: 14.5px; line-height: 1.85; color: #334155;" class="dark:text-slate-300">
                    تتيح هذه الصفحة متابعة أداء الموظف الفردي بدقة وحيادية تامة. تتوفر عبر مدخلين رئيسيين:
                    <br>1. <strong>صفحة «لوحتي»:</strong> يراها الموظف المسجل بنفسه لمتابعة تكليفاته الموكلة إليه ونواقصه.
                    <br>2. <strong>ملف الموظف السعودي والأجنبي:</strong> يفتحها المشرف أو الإدارة عبر زر «الأداء» في جدول الموظفين أو صفحة التعديل.
                </p>

                <div class="carnet-tips-box" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px; margin-top: 16px;">
                    <div class="carnet-tip-item" style="border-color: #0284c7; background: #f0f9ff; color: #0369a1; padding: 14px; border-radius: 10px; border-right: 4px solid #0284c7;" class="dark:bg-slate-900 dark:border-sky-800 dark:text-sky-300">
                        <strong>🎯 درجة التكليف الموزونة:</strong>
                        <div style="font-size: 12.5px; margin-top: 4px;">
                            تحسب المعادلة: <code>مجموع (الدرجة × الوزن) ÷ مجموع الأوزان × 100</code>، لتعكس الأهمية النسبية لكل مهمة دون مساواة المهمة البسيطة بالمعقدة.
                        </div>
                    </div>

                    <div class="carnet-tip-item" style="border-color: #10b981; background: #f0fdf4; color: #047857; padding: 14px; border-radius: 10px; border-right: 4px solid #10b981;" class="dark:bg-slate-900 dark:border-emerald-800 dark:text-emerald-300">
                        <strong>⚡ نسبة الإنجاز في الموعد:</strong>
                        <div style="font-size: 12.5px; margin-top: 4px;">
                            تقيس الالتزام الدقيق بالوقت: <code>المهام المكتملة في تاريخ الاستحقاق أو قبله ÷ إجمالي المكتمل ذي التواريخ</code>.
                        </div>
                    </div>

                    <div class="carnet-tip-item" style="border-color: #f59e0b; background: #fffbeb; color: #b45309; padding: 14px; border-radius: 10px; border-right: 4px solid #f59e0b;" class="dark:bg-slate-900 dark:border-amber-800 dark:text-amber-300">
                        <strong>⏱️ تفصيل عدادات الحالات:</strong>
                        <div style="font-size: 12.5px; margin-top: 4px;">
                            • <strong>متأخرة الآن:</strong> مفتوحة وتجاوزت موعدها المحدد.<br>
                            • <strong>مؤجلة:</strong> مستثناة نظامياً من الخصم.<br>
                            • <strong>جارية ومعلقة:</strong> قيد التنفيذ ضمن وقتها الطبيعي.
                        </div>
                    </div>

                    <div class="carnet-tip-item" style="border-color: #8b5cf6; background: #f5f3ff; color: #6d28d9; padding: 14px; border-radius: 10px; border-right: 4px solid #8b5cf6;" class="dark:bg-slate-900 dark:border-purple-800 dark:text-purple-300">
                        <strong>🔗 روابط السجلات التفاعلية:</strong>
                        <div style="font-size: 12.5px; margin-top: 4px;">
                            الضغط على رقم التكليف أو رقم المعاملة يفتح سجله فوراً برابط مباشر مع احترام كامل لصلاحيات المستخدم.
                        </div>
                    </div>
                </div>
            </section>

            <!-- ==========================================================================
                 القسم 3: تقييم أداء المشرفين بالمناوبات (معادلة 50/50)
                 ========================================================================== -->
            <section id="sec-supervisor-eval" class="carnet-card carnet-searchable-perf" style="padding: 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px;">
                <div class="carnet-card-title-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                    <span class="carnet-card-icon-badge" style="width: 34px; height: 34px; border-radius: 8px; background: rgba(22, 163, 74, 0.12); color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 18px;">👨‍💼</span>
                    <h2 class="carnet-card-title" style="font-size: 18px; font-weight: 800; margin: 0; color: #0f172a;" class="dark:text-white">تقييم أداء المشرفين بالمناوبات</h2>
                </div>

                <p class="carnet-card-text" style="font-size: 14.5px; line-height: 1.85; color: #334155;" class="dark:text-slate-300">
                    شاشة مخصصة تضمن حوكمة عادلة لقياس أداء المشرفين؛ فلا يُقاس المشرف بأداء فريقه فقط ويهمل مهامه الشخصية، ولا يُقاس بمهامه الشخصية ويهمل متابعة فريقه الميداني.
                </p>

                <!-- معادلة الـ 50/50 الشهيرة -->
                <div style="background: #f0fdf4; border: 2px solid #86efac; border-radius: 14px; padding: 20px; margin-top: 16px;" class="dark:bg-slate-900 dark:border-emerald-800">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
                        <h3 style="font-size: 16px; font-weight: 900; color: #14532d; margin: 0;" class="dark:text-emerald-300">⚖️ المعادلة المتوازنة لتقييم المشرف (50% + 50%)</h3>
                        <span style="background: #16a34a; color: #ffffff; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 800;">معتمدة إدارياً</span>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr auto 1fr; gap: 14px; align-items: center;">
                        <div style="background: #ffffff; padding: 14px; border-radius: 10px; border: 1px solid #bbf7d0; text-align: center;" class="dark:bg-slate-800 dark:border-slate-700">
                            <span style="font-size: 24px; font-weight: 900; color: #15803d; display: block;">50%</span>
                            <strong style="font-size: 13.5px; color: #0f172a; display: block; margin-top: 4px;" class="dark:text-white">أداء الفريق في المناوبات</strong>
                            <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;" class="dark:text-slate-400">
                                نسبة إنجاز موظفي الفريق لمهامهم المستحقة في الموعد أثناء فترات إشراف ومناوبة المشرف المسجلة.
                            </p>
                        </div>

                        <div style="font-size: 24px; font-weight: 900; color: #16a34a; text-align: center;">+</div>

                        <div style="background: #ffffff; padding: 14px; border-radius: 10px; border: 1px solid #bbf7d0; text-align: center;" class="dark:bg-slate-800 dark:border-slate-700">
                            <span style="font-size: 24px; font-weight: 900; color: #0284c7; display: block;">50%</span>
                            <strong style="font-size: 13.5px; color: #0f172a; display: block; margin-top: 4px;" class="dark:text-white">أداء المشرف الشخصي</strong>
                            <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;" class="dark:text-slate-400">
                                نسبة إنجاز المشرف نفسه للتكليفات المسندة إليه شخصياً ومعاملات الخدمات المستحقة عليه.
                            </p>
                        </div>
                    </div>

                    <div style="margin-top: 14px; padding-top: 12px; border-top: 1px dashed #86efac; font-size: 13px; color: #166534;" class="dark:border-emerald-800 dark:text-emerald-300">
                        <strong>الدرجة النهائية = </strong> متوسط النتيجتين من 100. تُعرض في الشهر الجاري بعنوان «التقييم الحالي حتى تاريخ اليوم»، وتتحول تلقائياً إلى «التقييم النهائي» عند إغلاق الشهر.
                    </div>
                </div>

                <!-- زر الإجراء في جدول المناوبات -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; margin-top: 14px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;" class="dark:bg-slate-900 dark:border-slate-800">
                    <div>
                        <strong style="font-size: 13.5px; color: #0f172a;" class="dark:text-white">💡 كيفية فتح تقييم المشرف مباشرة من جدول المناوبات:</strong>
                        <p style="font-size: 12.5px; color: #64748b; margin: 2px 0 0 0;" class="dark:text-slate-400">
                            في شاشة «الإشراف والمناوبات»، اضغط زر «عرض التقييم» في سطر أي نوبة، ليفتح لك التقييم مفلتراً تلقائياً بالمشرف والشهر والمنشأة.
                        </p>
                    </div>
                    <a href="/supervision-shifts" class="carnet-btn carnet-btn-outline" style="font-size: 12.5px;">
                        <span>الانتقال لجدول المناوبات</span>
                        <span>⇦</span>
                    </a>
                </div>
            </section>

            <!-- ==========================================================================
                 القسم 4: معادلة المقياس والخصومات واسترداد النقاط
                 ========================================================================== -->
            <section id="sec-score-rules" class="carnet-card carnet-searchable-perf" style="padding: 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px;">
                <div class="carnet-card-title-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                    <span class="carnet-card-icon-badge" style="width: 34px; height: 34px; border-radius: 8px; background: rgba(239, 68, 68, 0.12); color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 18px;">📐</span>
                    <h2 class="carnet-card-title" style="font-size: 18px; font-weight: 800; margin: 0; color: #0f172a;" class="dark:text-white">معادلة مقياس الأداء (0-100) وقواعد استرداد النقاط</h2>
                </div>

                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 18px; margin-bottom: 16px;" class="dark:bg-slate-900 dark:border-red-900/50">
                    <div style="font-size: 16px; font-weight: 800; color: #991b1b; margin-bottom: 6px;" class="dark:text-red-300">
                        معادلة الاحتساب الآلي اللحظي:
                    </div>
                    <div style="background: #ffffff; padding: 12px; border-radius: 8px; font-size: 15px; font-weight: 800; color: #b91c1c; text-align: center; border: 1px dashed #fca5a5; line-height: 1.8;" class="dark:bg-slate-800 dark:border-red-800 dark:text-red-400">
                        الدرجة = 100 - (عدد المهام المتأخرة × 4) - (عدد نواقص المعاملات × 2) [بحيث لا تقل الدرجة عن صفر]
                    </div>
                </div>

                <div class="carnet-workflow-box" style="display: flex; flex-direction: column; gap: 12px;">
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; display: flex; align-items: flex-start; gap: 12px;" class="dark:bg-slate-800 dark:border-slate-700">
                        <span style="background: #dc2626; color: #fff; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; flex-shrink: 0;">-4</span>
                        <div>
                            <strong style="color: #0f172a; font-size: 14px; display: block;" class="dark:text-white">خصم 4 نقاط عن كل مهمة متأخرة مفتوحة:</strong>
                            <p style="font-size: 13px; color: #64748b; margin: 3px 0 0 0;" class="dark:text-slate-400">
                                أي مهمة تجاوز تاريخ استحقاقها ولم يتم إكمالها أو تأجيلها تخصم 4 نقاط من رصيد القسم والمنشأة.
                            </p>
                        </div>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; display: flex; align-items: flex-start; gap: 12px;" class="dark:bg-slate-800 dark:border-slate-700">
                        <span style="background: #f59e0b; color: #fff; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; flex-shrink: 0;">-2</span>
                        <div>
                            <strong style="color: #0f172a; font-size: 14px; display: block;" class="dark:text-white">خصم نقطتين عن كل نقص في معاملة خدمة:</strong>
                            <p style="font-size: 13px; color: #64748b; margin: 3px 0 0 0;" class="dark:text-slate-400">
                                أي نقص مفتوح في معاملات الخدمات تجاوز موعد استحقاقه يخصم نقطتين من رصيد القسم.
                            </p>
                        </div>
                    </div>

                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 14px; display: flex; align-items: flex-start; gap: 12px;" class="dark:bg-slate-800 dark:border-emerald-800">
                        <span style="background: #16a34a; color: #fff; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; flex-shrink: 0;">🔄</span>
                        <div>
                            <strong style="color: #14532d; font-size: 14px; display: block;" class="dark:text-emerald-300">مبدأ استرداد النقاط فورياً عند الإنجاز:</strong>
                            <p style="font-size: 13px; color: #166534; margin: 3px 0 0 0;" class="dark:text-emerald-200">
                                الدرجة غير ثابتة ولا تُخزن كأرقام ميتة في قاعدة البيانات؛ بمجرد أن يقوم الموظف بإنجاز المهمة المتأخرة أو إغلاق النقص، <strong>تسترد المنشأة والقسم النقاط المخصومة فوراً</strong> في نفس اللحظة بمجرد تحديث الصفحة!
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ==========================================================================
                 القسم 5: تصعيدات الأداء وعتبة 65
                 ========================================================================== -->
            <section id="sec-escalations" class="carnet-card carnet-searchable-perf" style="padding: 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px;">
                <div class="carnet-card-title-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                    <span class="carnet-card-icon-badge" style="width: 34px; height: 34px; border-radius: 8px; background: rgba(220, 38, 38, 0.12); color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 18px;">🚨</span>
                    <h2 class="carnet-card-title" style="font-size: 18px; font-weight: 800; margin: 0; color: #0f172a;" class="dark:text-white">نظام تصعيدات الأداء والإنذار المبكر</h2>
                </div>

                <p class="carnet-card-text" style="font-size: 14.5px; line-height: 1.85; color: #334155;" class="dark:text-slate-300">
                    يعمل في خلفية النظام مراقب آلي ذكي لفحص أداء كافة الأقسام كل ساعة بصورة دورية:
                </p>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; margin-top: 14px;">
                    <div style="background: #fff1f2; border: 1px solid #fecdd3; border-radius: 12px; padding: 16px;" class="dark:bg-slate-900 dark:border-rose-950">
                        <strong style="color: #be123c; font-size: 14px; display: block; margin-bottom: 6px;" class="dark:text-rose-400">1. شرط فتح التصعيد (أقل من 65 نقطة):</strong>
                        <p style="font-size: 12.5px; color: #881337; margin: 0; line-height: 1.7;" class="dark:text-rose-300">
                            عندما تنخفض درجة أي قسم تشغيلي إلى ما دون 65 نقطة، يفتح النظام تلقائياً ملف تصعيد إداري، ويرسل إشعارات فورية (أيقونة الجرس) للمدير العام ومالك المنشأة.
                        </p>
                    </div>

                    <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 16px;" class="dark:bg-slate-900 dark:border-amber-950">
                        <strong style="color: #b45309; font-size: 14px; display: block; margin-bottom: 6px;" class="dark:text-amber-400">2. تسجيل إجراء الإدارة:</strong>
                        <p style="font-size: 12.5px; color: #78350f; margin: 0; line-height: 1.7;" class="dark:text-amber-300">
                            من شاشة «تصعيدات الأداء»، تستطيع الإدارة توثيق الإجراء المتخذ (اجتماع، إعادة توزيع مهام، لفت نظر) ليبقى محفوظاً في السجل الإداري.
                        </p>
                    </div>

                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 16px;" class="dark:bg-slate-900 dark:border-emerald-950">
                        <strong style="color: #15803d; font-size: 14px; display: block; margin-bottom: 6px;" class="dark:text-emerald-400">3. التعافي والإغلاق التلقائي:</strong>
                        <p style="font-size: 12.5px; color: #14532d; margin: 0; line-height: 1.7;" class="dark:text-emerald-300">
                            عندما يُنهي موظفو القسم المهام المتأخرة وترتفع الدرجة إلى 65 فأعلى، يُغلق النظام ملف التصعيد آلياً بسبب «التعافي وتحسن الأداء» دون الحاجة لتدخل يدوي.
                        </p>
                    </div>
                </div>
            </section>

            <!-- ==========================================================================
                 القسم 6: الأبعاد والتبويبات الخمسة للوحة التحكم
                 ========================================================================== -->
            <section id="sec-five-tabs" class="carnet-card carnet-searchable-perf" style="padding: 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px;">
                <div class="carnet-card-title-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                    <span class="carnet-card-icon-badge" style="width: 34px; height: 34px; border-radius: 8px; background: rgba(124, 58, 237, 0.12); color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 18px;">📊</span>
                    <h2 class="carnet-card-title" style="font-size: 18px; font-weight: 800; margin: 0; color: #0f172a;" class="dark:text-white">الأبعاد والتبويبات الخمسة للوحة تحكم الأداء</h2>
                </div>

                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <div style="border-right: 4px solid #4f46e5; padding: 12px 16px; background: #f8fafc; border-radius: 0 10px 10px 0;" class="dark:bg-slate-800">
                        <strong style="color: #4f46e5; font-size: 14px;">1. تبويب التشغيل والعمليات:</strong>
                        <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;" class="dark:text-slate-400">
                            درجة الأداء اللحظية، بطاقة المنشأة، مسار صدارة الأقسام، مؤشرات إنجاز التكليفات في الموعد، وكشوفات الخدمات التفصيلية.
                        </p>
                    </div>

                    <div style="border-right: 4px solid #06b6d4; padding: 12px 16px; background: #f8fafc; border-radius: 0 10px 10px 0;" class="dark:bg-slate-800">
                        <strong style="color: #0891b2; font-size: 14px;">2. تبويب الحضور والانصراف والإجازات:</strong>
                        <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;" class="dark:text-slate-400">
                            حلقات نسب الحضور في الموعد والتأخير والغياب، المعاينة الحية لأول 5 سجلات مع كشف وسطي كامل، وإحصائيات الموظفين في إجازة اليوم.
                        </p>
                    </div>

                    <div style="border-right: 4px solid #10b981; padding: 12px 16px; background: #f8fafc; border-radius: 0 10px 10px 0;" class="dark:bg-slate-800">
                        <strong style="color: #059669; font-size: 14px;">3. تبويب المالية والخزنة:</strong>
                        <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;" class="dark:text-slate-400">
                            رصيد الخزنة وحركتها، سندات القبض والتوريد المؤكدة، مطالبات العهد والرسوم، صافي مسيرات الرواتب والتحصيل، ونسب استرداد الديون.
                        </p>
                    </div>

                    <div style="border-right: 4px solid #f59e0b; padding: 12px 16px; background: #f8fafc; border-radius: 0 10px 10px 0;" class="dark:bg-slate-800">
                        <strong style="color: #d97706; font-size: 14px;">4. تبويب الامتثال والتراخيص والوثائق:</strong>
                        <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;" class="dark:text-slate-400">
                            شرائح انتهاء إقامات الموظفين (90، 180، 270 يوماً)، رخص العمل والتأمين الطبي، ومراقبة صلاحية السجلات التجارية وتراخيص البلدية والدفاع المدني.
                        </p>
                    </div>

                    <div style="border-right: 4px solid #ef4444; padding: 12px 16px; background: #f8fafc; border-radius: 0 10px 10px 0;" class="dark:bg-slate-800">
                        <strong style="color: #dc2626; font-size: 14px;">5. تبويب المخالفات والجزاءات:</strong>
                        <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;" class="dark:text-slate-400">
                            مراحل معالجة المخالفات، نسب المبالغ المسددة والمتبقية، توزيع المخالفات حسب الجهات الحكومية، وإمكانية فتح كشف مفصل لكل جهة.
                        </p>
                    </div>
                </div>
            </section>

            <!-- ==========================================================================
                 القسم 7: الأسئلة الشائعة والقواعد الإدارية
                 ========================================================================== -->
            <section id="sec-faq" class="carnet-card carnet-searchable-perf" style="padding: 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px;">
                <div class="carnet-card-title-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                    <span class="carnet-card-icon-badge" style="width: 34px; height: 34px; border-radius: 8px; background: rgba(79, 70, 229, 0.12); color: #4f46e5; display: flex; align-items: center; justify-content: center; font-size: 18px;">❓</span>
                    <h2 class="carnet-card-title" style="font-size: 18px; font-weight: 800; margin: 0; color: #0f172a;" class="dark:text-white">الأسئلة الشائعة حول منظومة الأداء</h2>
                </div>

                <div class="carnet-faq-accordion" style="display: flex; flex-direction: column; gap: 10px;">
                    <details class="carnet-faq-item" style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; background: #f8fafc;" class="dark:bg-slate-800 dark:border-slate-700">
                        <summary style="font-size: 14px; font-weight: 700; color: #0f172a; cursor: pointer; list-style: none; display: flex; align-items: center; justify-content: space-between;" class="dark:text-white">
                            <span>هل تؤثر المهام المؤجلة على خصم النقاط في لوحة الأداء؟</span>
                            <span style="color: #4f46e5;">▼</span>
                        </summary>
                        <div style="font-size: 13px; color: #64748b; margin-top: 8px; line-height: 1.7;" class="dark:text-slate-300">
                            لا. المهام المعتمدة بحالة «مؤجلة» لا تُعد متأخرة ولا يُخصم عنها أي نقطة من رصيد القسم أو المنشأة، وتظهر بعدادها الخاص للشفافية.
                        </div>
                    </details>

                    <details class="carnet-faq-item" style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; background: #f8fafc;" class="dark:bg-slate-800 dark:border-slate-700">
                        <summary style="font-size: 14px; font-weight: 700; color: #0f172a; cursor: pointer; list-style: none; display: flex; align-items: center; justify-content: space-between;" class="dark:text-white">
                            <span>ماذا يحدث للدرجة عند إنجاز مهمة كانت متأخرة؟</span>
                            <span style="color: #4f46e5;">▼</span>
                        </summary>
                        <div style="font-size: 13px; color: #64748b; margin-top: 8px; line-height: 1.7;" class="dark:text-slate-300">
                            تسترد المنشأة الـ 4 نقاط المخصومة فوراً في القراءة التالية؛ لأن حساب الدرجة آلي لحظي يعتمد على الحالات المفتوحة في لحظة العرض.
                        </div>
                    </details>

                    <details class="carnet-faq-item" style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; background: #f8fafc;" class="dark:bg-slate-800 dark:border-slate-700">
                        <summary style="font-size: 14px; font-weight: 700; color: #0f172a; cursor: pointer; list-style: none; display: flex; align-items: center; justify-content: space-between;" class="dark:text-white">
                            <span>لماذا تظهر درجة تقييم المشرف بعنوان «مبدئية» في بعض الأيام؟</span>
                            <span style="color: #4f46e5;">▼</span>
                        </summary>
                        <div style="font-size: 13px; color: #64748b; margin-top: 8px; line-height: 1.7;" class="dark:text-slate-300">
                            تظهر الدرجة «مبدئية حتى تاريخ اليوم» لأن الشهر لا يزال سارياً وهناك مهام ومناوبات لم تنتهِ فتراتها بعد، وتتحول تلقائياً إلى «التقييم النهائي» في الشهر المغلق.
                        </div>
                    </details>

                    <details class="carnet-faq-item" style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; background: #f8fafc;" class="dark:bg-slate-800 dark:border-slate-700">
                        <summary style="font-size: 14px; font-weight: 700; color: #0f172a; cursor: pointer; list-style: none; display: flex; align-items: center; justify-content: space-between;" class="dark:text-white">
                            <span>ما الفرق بين صفحة «لوحتي» وصفحة الأداء في ملف الموظف؟</span>
                            <span style="color: #4f46e5;">▼</span>
                        </summary>
                        <div style="font-size: 13px; color: #64748b; margin-top: 8px; line-height: 1.7;" class="dark:text-slate-300">
                            «لوحتي» خاصة بالموظف المسجل الدخول حالياً لمتابعة أعماله فقط، بينما صفحة الأداء في ملف الموظف مخصصة للمدراء والمشرفين لمراجعة وتقييم موظفيهم المصرح لهم بالاطلاع عليهم.
                        </div>
                    </details>

                    <details class="carnet-faq-item" style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; background: #f8fafc;" class="dark:bg-slate-800 dark:border-slate-700">
                        <summary style="font-size: 14px; font-weight: 700; color: #0f172a; cursor: pointer; list-style: none; display: flex; align-items: center; justify-content: space-between;" class="dark:text-white">
                            <span>أين يُرفع ملف الفيديو على السيرفر ليعمل المشغل؟</span>
                            <span style="color: #4f46e5;">▼</span>
                        </summary>
                        <div style="font-size: 13px; color: #64748b; margin-top: 8px; line-height: 1.7;" class="dark:text-slate-300">
                            يُرفع الملف باسم <code style="direction: ltr;">performance.mp4</code> داخل مجلد الفيديوهات على الخادم، وسيعمل المشغل فوراً في هذه الصفحة تلقائياً.
                        </div>
                    </details>
                </div>
            </section>

        </main>
    </div>

</div>

<!-- السكربتات التفاعلية الخاصة بدليل الأداء -->
<script>
    function copyPerformanceShareLink() {
        const url = window.location.origin + window.location.pathname + '?activeGroup=reports&activeSub=performance#sec-video';
        navigator.clipboard.writeText(url).then(function() {
            alert('تم نسخ رابط شرح وفيديو لوحة الأداء بنجاح!');
        }).catch(function() {
            prompt('انسخ رابط الصفحة من هنا:', url);
        });
    }

    function filterPerformanceContent() {
        const query = (document.getElementById('carnet-performance-search')?.value || '').trim().toLowerCase();
        const sections = document.querySelectorAll('.carnet-searchable-perf');

        sections.forEach(sec => {
            if (!query) {
                sec.style.display = '';
                return;
            }
            const text = sec.innerText.toLowerCase();
            sec.style.display = text.includes(query) ? '' : 'none';
        });
    }

    // تفعيل التمرير السلس للروابط في دليل الأداء
    document.querySelectorAll('.carnet-perf-subtabs a, .carnet-perf-guide-wrapper .carnet-index-item a').forEach(link => {
        link.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId && targetId.startsWith('#')) {
                const target = document.querySelector(targetId);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    document.querySelectorAll('.carnet-perf-subtabs a').forEach(t => t.classList.remove('active'));
                    const matchTab = document.querySelector(`.carnet-perf-subtabs a[href="${targetId}"]`);
                    if (matchTab) matchTab.classList.add('active');
                }
            }
        });
    });
</script>
