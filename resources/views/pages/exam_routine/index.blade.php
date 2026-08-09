@extends('tyro-dashboard::layouts.admin')

@section('title', 'Exam Routine Management')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<!-- Load Google Fonts Noto Serif Bengali -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<!-- Load Noto Serif Bengali Font styles -->
<style>
    @font-face {
        font-family: 'Noto Serif Bengali';
        src: url("{{ asset('fonts/NotoSerifBengali-Regular.ttf') }}") format('truetype');
        font-weight: normal;
        font-style: normal;
    }
    @font-face {
        font-family: 'Noto Serif Bengali';
        src: url("{{ asset('fonts/NotoSerifBengali-Bold.ttf') }}") format('truetype');
        font-weight: bold;
        font-style: normal;
    }

    /* Table padding override to align with MACS Design guidelines */
    .table th, .table td {
        padding: 0.875rem 1rem !important;
    }

    /* ==========================================
       🔥 EXAM PRINT CSS (A4 Portrait Formal)
       ========================================== */
    /* Screen styles: ALWAYS force-hide dual shift print layout and all its children on screen */
    @media screen {
        #dualShiftPrintLayout,
        #dualShiftPrintLayout * {
            display: none !important;
        }
    }

    @media print {
        @page { size: A4 portrait; margin: 2mm 4mm; }
        
        /* Hide all layout sidebars, topbar, alerts, and other UI controls */
        .sidebar, 
        .topbar,
        .sidebar-overlay,
        .mobile-menu-btn,
        .no-print,
        .alert,
        #globalModal {
            display: none !important;
        }

        /* Reset layout containers to flow normally without grids or sidebars */
        .dashboard-layout,
        .main-content,
        .page-content,
        .grid,
        .lg:col-span-8,
        div[x-data="examRoutinePage()"] {
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
            border: none !important;
            width: 100% !important;
            max-width: 100% !important;
            display: block !important;
            float: none !important;
            box-shadow: none !important;
        }

        /* If we are NOT printing dual shift, hide #dualShiftPrintLayout */
        body:not(.print-dual) #dualShiftPrintLayout,
        body:not(.print-dual) #dualShiftPrintLayout * {
            display: none !important;
        }

        /* If we ARE printing dual shift, display #dualShiftPrintLayout */
        body.print-dual #dualShiftPrintLayout {
            display: block !important;
            position: absolute !important; 
            left: 0; 
            top: 0; 
            width: 100%; 
            padding: 0; 
            box-shadow: none !important; 
            border: none !important; 
            background: #fff !important;
        }

        body.print-dual #dualShiftPrintLayout, 
        body.print-dual #dualShiftPrintLayout * { 
            visibility: visible !important; 
            color: #000 !important; 
            -webkit-print-color-adjust: exact !important; 
            print-color-adjust: exact !important;
            font-family: 'Noto Serif Bengali', serif !important;
        }

        body.print-dual #dualShiftPrintLayout .flex {
            display: flex !important;
        }

        body.print-dual #dualShiftPrintLayout .grid {
            display: grid !important;
        }

        th.routine-th,
        tr.routine-header-tr th {
            background-color: #d1d5db !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Specific view overrides when printing single shift */
        body.print-single #dualShiftPrintLayout {
            display: none !important;
        }

        /* Specific view overrides when printing dual shift */
        body.print-dual #printableRoutine,
        body.print-dual .lg\:col-span-4,
        body.print-dual .lg\:col-span-8 {
            display: none !important;
        }
        
        .page-break { 
            page-break-after: always !important; 
            break-after: page !important;
            display: block !important;
            height: 0 !important;
            clear: both !important;
        }

        /* Spacing layout for Page 2 Parent Flyers */
        body.print-dual #dualShiftPrintLayout .print-page {
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            height: 282mm !important;
            box-sizing: border-box !important;
            background-color: #fff !important;
        }

        body.print-dual #dualShiftPrintLayout .flyer-half {
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            height: 138mm !important;
            box-sizing: border-box !important;
            background-color: #fff !important;
        }

        /* Print Header - Formal School Style */
        .school-header { text-align: center; border-bottom: 3px double #000; padding-bottom: 15px; margin-bottom: 20px; }
        .school-header h1 { font-size: 28px !important; font-weight: 900 !important; margin: 0 !important; text-transform: uppercase; font-family: 'Times New Roman', serif; }
        .school-header h3 { font-size: 20px !important; margin: 5px 0 0 0 !important; text-decoration: underline; }
        .school-header p { font-size: 16px !important; margin: 5px 0 0 0 !important; font-weight: bold; }

        /* Exam Table */
        th, td { border: 1px solid #000 !important; padding: 12px !important; text-align: center; font-size: 16px !important; }
        th { background-color: #e5e7eb !important; font-weight: bold !important; text-transform: uppercase; }
        td { font-weight: 600 !important; }
    }
</style>

<!-- Load Main Scripts in head to avoid Alpine/Livewire race conditions -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    // Custom Date Picker component data definition
    function datePicker(initialValue = '') {
        return {
            show: false,
            value: initialValue || new Date().toISOString().split('T')[0],
            currentYear: new Date().getFullYear(),
            currentMonth: new Date().getMonth(),
            days: [],
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            
            init() {
                this.generateCalendar();
                this.$watch('value', val => {
                    if (val) {
                        const d = new Date(val);
                        this.currentYear = d.getFullYear();
                        this.currentMonth = d.getMonth();
                        this.generateCalendar();
                    }
                });
            },
            
            generateCalendar() {
                const firstDayIndex = new Date(this.currentYear, this.currentMonth, 1).getDay();
                const totalDays = new Date(this.currentYear, this.currentMonth + 1, 0).getDate();
                
                const days = [];
                for (let i = 0; i < firstDayIndex; i++) {
                    days.push({ day: '', isCurrentMonth: false });
                }
                for (let i = 1; i <= totalDays; i++) {
                    days.push({ day: i, isCurrentMonth: true });
                }
                this.days = days;
            },
            
            prevMonth() {
                if (this.currentMonth === 0) {
                    this.currentMonth = 11;
                    this.currentYear--;
                } else {
                    this.currentMonth--;
                }
                this.generateCalendar();
            },
            
            nextMonth() {
                if (this.currentMonth === 11) {
                    this.currentMonth = 0;
                    this.currentYear++;
                } else {
                    this.currentMonth++;
                }
                this.generateCalendar();
            },
            
            selectDay(day) {
                if (!day) return;
                const formattedMonth = String(this.currentMonth + 1).padStart(2, '0');
                const formattedDay = String(day).padStart(2, '0');
                this.value = `${this.currentYear}-${formattedMonth}-${formattedDay}`;
                this.show = false;
                this.$dispatch('date-selected', this.value);
            },
            
            formatDisplay(val) {
                if (!val) return 'Select Date';
                const d = new Date(val);
                return d.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
            }
        }
    }

    // Custom Time Picker component data definition
    function timePicker(initialValue = '') {
        return {
            show: false,
            value: initialValue || '09:00', // HH:MM (24h)
            hour: 9,
            minute: 0,
            period: 'AM',
            
            init() {
                this.parseValue(this.value);
                this.$watch('value', val => {
                    if (val) this.parseValue(val);
                });
            },
            
            parseValue(val) {
                if (!val) return;
                const parts = val.split(':');
                let h = parseInt(parts[0]);
                this.minute = parseInt(parts[1] || '0');
                
                if (h >= 12) {
                    this.period = 'PM';
                    this.hour = h === 12 ? 12 : h - 12;
                } else {
                    this.period = 'AM';
                    this.hour = h === 0 ? 12 : h;
                }
            },
            
            selectHour(h) {
                this.hour = h;
                this.updateValue();
            },
            
            selectMinute(m) {
                this.minute = m;
                this.updateValue();
            },
            
            selectPeriod(p) {
                this.period = p;
                this.updateValue();
            },
            
            updateValue() {
                let h24 = this.hour;
                if (this.period === 'PM') {
                    h24 = this.hour === 12 ? 12 : this.hour + 12;
                } else {
                    h24 = this.hour === 12 ? 0 : this.hour;
                }
                const formattedHour = String(h24).padStart(2, '0');
                const formattedMinute = String(this.minute).padStart(2, '0');
                this.value = `${formattedHour}:${formattedMinute}`;
                this.$dispatch('time-selected', this.value);
            },
            
            formatDisplay(val) {
                if (!val) return 'Select Time';
                const parts = val.split(':');
                let h = parseInt(parts[0]);
                let m = parseInt(parts[1] || '0');
                let p = h >= 12 ? 'PM' : 'AM';
                let displayHour = h % 12;
                if (displayHour === 0) displayHour = 12;
                return `${String(displayHour).padStart(2, '0')}:${String(m).padStart(2, '0')} ${p}`;
            }
        }
    }

    @php
        $shift1ClassIds = [];
        $shift2ClassIds = [];
        foreach($classes as $c) {
            $name = strtolower(trim($c->class_name));
            if (in_array($name, ['play', 'nursery', 'one', 'two', 'three', 'four', '১ম', '২য়', '৩য়', '৪র্থ', 'প্লে', 'নার্সারী', 'প্রথম', 'দ্বিতীয়', 'তৃতীয়', 'চতুর্থ'])) {
                $shift1ClassIds[] = (string)$c->id;
            } else {
                $shift2ClassIds[] = (string)$c->id;
            }
        }
    @endphp

    function examRoutinePage() {
        return {
            dropdownOpen: null,
            sessionText: '{{ $sessions->first()->session_name ?? "Select Session" }}',
            examText: '{{ $exams->first()->name ?? "Select Exam" }}',
            classText: '{{ $classes->first()->class_name ?? "Select Class..." }}',
            subjectText: 'Select Subject...',
            
            printSessionYearId: '{{ $sessions->first()->id ?? "" }}',
            printExamId: '{{ $exams->first()->id ?? "" }}',
            examsList: @json($exams),

            printExamName: 'Term Examination',
            printClassInfo: 'Class Name',
            
            activeTab: 'planner',
            savedRoutines: [],
            editMode: false,
            editingSlotId: null,
            showPrintModal: false,
            
            printMode: 'general',
            batchClassId: '',
            batchTuitionFee: '',
            batchExamFee: '',
            batchOtherFee: '',
            batchStudents: [],
            batchClassSlots: [],
            batchClassName: '',
            
            shift1ClassNames: [],
            shift2ClassNames: [],
            shift1Rows: [],
            shift2Rows: [],
            
            printConfig: {
                shift1: {
                    name: 'প্রথম শিফট',
                    classRange: 'প্লে - ৪র্থ',
                    timeLabel: 'সময় : সকাল ৯.০০ থেকে ১১.০০ টা',
                    footnote: '[ প্লে, নার্সারী, প্রথম ও দ্বিতীয় শ্রেণীর S.B.A পরীক্ষা সকাল ১০:৩০ মিনিটে শুরু হবে ]',
                    classes: @json($shift1ClassIds)
                },
                shift2: {
                    name: 'দ্বিতীয় শিফট',
                    classRange: '৫ম - ৯ম',
                    timeLabel: 'সময় : দুপুর ১২.০০ থেকে ০২.০০ টা',
                    footnote: '',
                    classes: @json($shift2ClassIds)
                },
                announcement: {
                    title: '২য় সাময়িক পরীক্ষা-২০২৬',
                    dueLimit: '১৪/০৮/২৬',
                    text: 'আসসালামু আলাইকুম। আসছে ১৭ আগস্ট ২০২৬ খ্রী: হতে ২য় সাময়িক পরীক্ষা অপর পাতায় সূচী অনুযায়ী অনুষ্ঠিত হতে যাচ্ছে ইনশাল্লাহ। এজন্য পরীক্ষা ফি, আগস্ট ২০২৬ খ্রী: পর্যন্ত বেতন সহ সকল পাওনাদি ১৪/০৮/২৬ খ্রী: এর মধ্যে পরিশোধ করে পরীক্ষার প্রবেশ পত্র সংগ্রহ করতে হবে। প্রবেশ পত্র ব্যতীত কোন শিক্ষার্থী পরীক্ষায় অংশ গ্রহণ করতে পারবে না।',
                    principalTitle: 'অধ্যক্ষ',
                    principalName: 'মা-আসসালাম',
                    phone: '০১৮১৬-২২০৩০০'
                }
            },

            form: {
                session_year_id: '{{ $sessions->first()->id ?? "" }}',
                exam_id: '{{ $exams->first()->id ?? "" }}',
                class_id: '{{ $classes->first()->id ?? "" }}',
                subject_id: '',
                exam_date: new Date().toISOString().split('T')[0],
                room_number: '',
                start_time: '09:00',
                end_time: '12:00'
            },
            
            routineSlots: [],
            noData: true,
            loading: false,
            saving: false,
            
            init() {
                let defaultShift1 = @json($shift1ClassIds);
                let defaultShift2 = @json($shift2ClassIds);

                this.printConfig.shift1.classes = defaultShift1;
                this.printConfig.shift2.classes = defaultShift2;

                let cached = localStorage.getItem('dualShiftPrintConfig');
                if (cached && !cached.includes('First Shift')) {
                    try {
                        let parsed = JSON.parse(cached);
                        if (parsed.shift1) {
                            this.printConfig.shift1.name = parsed.shift1.name || this.printConfig.shift1.name;
                            this.printConfig.shift1.classRange = parsed.shift1.classRange || this.printConfig.shift1.classRange;
                            this.printConfig.shift1.timeLabel = parsed.shift1.timeLabel || this.printConfig.shift1.timeLabel;
                            this.printConfig.shift1.footnote = parsed.shift1.footnote !== undefined ? parsed.shift1.footnote : this.printConfig.shift1.footnote;
                            if (Array.isArray(parsed.shift1.classes) && parsed.shift1.classes.length > 0) {
                                let valid = parsed.shift1.classes.filter(id => defaultShift1.concat(defaultShift2).includes(String(id)));
                                if (valid.length > 0) this.printConfig.shift1.classes = valid;
                            }
                        }
                        if (parsed.shift2) {
                            this.printConfig.shift2.name = parsed.shift2.name || this.printConfig.shift2.name;
                            this.printConfig.shift2.classRange = parsed.shift2.classRange || this.printConfig.shift2.classRange;
                            this.printConfig.shift2.timeLabel = parsed.shift2.timeLabel || this.printConfig.shift2.timeLabel;
                            this.printConfig.shift2.footnote = parsed.shift2.footnote !== undefined ? parsed.shift2.footnote : this.printConfig.shift2.footnote;
                            if (Array.isArray(parsed.shift2.classes) && parsed.shift2.classes.length > 0) {
                                let valid = parsed.shift2.classes.filter(id => defaultShift1.concat(defaultShift2).includes(String(id)));
                                if (valid.length > 0) this.printConfig.shift2.classes = valid;
                            }
                        }
                        if (parsed.announcement) {
                            this.printConfig.announcement = { ...this.printConfig.announcement, ...parsed.announcement };
                        }
                    } catch (e) {
                        console.error(e);
                    }
                }
                
                this.loadRoutine();
                
                // Watch modal state to disable/enable page scroll
                this.$watch('showPrintModal', val => {
                    if (val) {
                        document.body.classList.add('overflow-hidden');
                    } else {
                        document.body.classList.remove('overflow-hidden');
                    }
                });
            },

            get chunkedBatchStudents() {
                let chunks = [];
                for (let i = 0; i < this.batchStudents.length; i += 2) {
                    chunks.push([
                        this.batchStudents[i],
                        this.batchStudents[i + 1] || null
                    ]);
                }
                return chunks;
            },
            
            selectSession(id, name) {
                this.form.session_year_id = id;
                this.sessionText = name;
                this.dropdownOpen = null;
                this.loadRoutine();
            },
            
            selectExam(id, name) {
                this.form.exam_id = id;
                this.examText = name;
                this.dropdownOpen = null;
                this.loadRoutine();
            },
            
            selectClass(id, name) {
                this.form.class_id = id;
                this.classText = name;
                this.form.subject_id = '';
                this.subjectText = 'Select Subject...';
                this.dropdownOpen = null;
                this.loadRoutine();
            },
            
            selectSubject(id, name) {
                this.form.subject_id = id;
                this.subjectText = name;
                this.dropdownOpen = null;
            },
            
            formatTime(timeStr) {
                if (!timeStr) return '';
                let [hours, minutes] = timeStr.split(':');
                let ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12 || 12;
                return `${hours}:${minutes} ${ampm}`;
            },

            toBanglaNum(num) {
                if (num === null || num === undefined) return '';
                const banglaDigits = {'0':'০','1':'১','2':'২','3':'৩','4':'৪','5':'৫','6':'৬','7':'৭','8':'৮','9':'৯'};
                return String(num).split('').map(char => banglaDigits[char] || char).join('');
            },

            formatDateBangla(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                const day = String(d.getDate()).padStart(2, '0');
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const year = String(d.getFullYear()).substring(2, 4);
                return this.toBanglaNum(`${day}/${month}/${year}`);
            },

            getDayBangla(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                const dayIndex = d.getDay();
                const days = ['রবিবার', 'সোমবার', 'মঙ্গলবার', 'বুধবার', 'বৃহঃবার', 'শুক্রবার', 'শনিবার'];
                return days[dayIndex];
            },

            fixMojibake(str) {
                if (!str) return '';
                if (typeof str !== 'string') return String(str);
                try {
                    if (/[\u00C0-\u00FF]/.test(str)) {
                        let decoded = decodeURIComponent(escape(str));
                        if (decoded) return decoded;
                    }
                } catch(e) {}
                return str;
            },

            translateClass(cls) {
                if (!cls) return '';
                cls = this.fixMojibake(cls);
                let c = cls.toLowerCase().trim();
                if (c === 'play' || c === 'প্লে') return 'প্লে';
                if (c === 'nursery' || c === 'নার্সারী') return 'নার্সারী';
                if (c === 'one' || c === 'প্রথম') return 'প্রথম';
                if (c === 'two' || c === 'দ্বিতীয়') return 'দ্বিতীয়';
                if (c === 'three' || c === 'তৃতীয়') return 'তৃতীয়';
                if (c === 'four' || c === 'চতুর্থ') return 'চতুর্থ';
                if (c === 'five' || c === 'পঞ্চম') return 'পঞ্চম';
                if (c === 'six' || c === 'ষষ্ঠ') return 'ষষ্ঠ';
                if (c === 'seven' || c === 'সপ্তম') return 'সপ্তম';
                if (c === 'eight' || c === 'অষ্টম') return 'অষ্টম';
                if (c === 'nine' || c === 'নবম') return 'নবম';
                if (c === 'ten' || c === 'দশম') return 'দশম';
                return cls;
            },

            translateSubject(sub) {
                if (!sub) return '×';
                sub = this.fixMojibake(sub);
                let s = sub.toLowerCase().trim();
                if (s.includes('bangla') || s.includes('বাংলা') || s.includes('বাং')) {
                    if (s.includes('1st') || s.includes('১ম')) return 'বাংলা ১ম পত্র';
                    if (s.includes('2nd') || s.includes('২য়')) return 'বাংলা ২য় পত্র';
                    return 'বাংলা';
                }
                if (s.includes('english') || s.includes('ইংরেজি') || s.includes('ইঙ্গরেজী') || s.includes('ইংশরেজী')) {
                    if (s.includes('1st') || s.includes('১ম')) return 'ইংরেজি ১ম পত্র';
                    if (s.includes('2nd') || s.includes('২য়')) return 'ইংরেজি ২য় পত্র';
                    return 'ইংরেজি';
                }
                if (s.includes('math') || s.includes('গণিত') || s.includes('গণি')) return 'গণিত';
                if (s.includes('arabic') || s.includes('আরবী') || s.includes('ধর্ম')) return 'আরবী/ধর্মশিক্ষা';
                if (s.includes('drawing') || s.includes('ড্রইং') || s.includes('অঙ্কন')) return 'ড্রইং';
                if (s.includes('science') || s.includes('বিজ্ঞান')) return 'বিজ্ঞান';
                if (s.includes('islam') || s.includes('ইসলাম')) return 'ইসলাম শিক্ষা';
                if (s.includes('physical') || s.includes('শারীরিক')) return 'শারীরিক শিঃ';
                if (s.includes('s.b.a') || s.includes('sba')) return 'S.B.A';
                if (s.includes('gk') || s.includes('general knowledge') || s.includes('সাধারণ')) return 'সাঃ জ্ঞান';
                if (s.includes('social') || s.includes('সমাজ')) return 'সমাজ';
                if (s.includes('ict') || s.includes('তথ্য')) return 'তথ্য ও যোগাঃ';
                if (s.includes('physics') || s.includes('পদার্থ')) return 'পদার্থ/ইতিহাস';
                if (s.includes('agriculture') || s.includes('কৃষি')) return 'কৃষিশিক্ষা';
                if (s.includes('bgs') || s.includes('বাংলাদেশ')) return 'বাংলাদেশ ও বিশ্বঃ';
                return sub;
            },

            getSubjectStyle(rawSub) {
                let sub = this.translateSubject(rawSub);
                if (!sub) return 'font-size: 10px; white-space: nowrap;';
                let len = sub.length;
                if (len <= 7) {
                    return 'font-size: 10px; white-space: nowrap;';
                } else if (len <= 11) {
                    return 'font-size: 8.5px; white-space: nowrap; letter-spacing: -0.2px;';
                } else if (len <= 15) {
                    return 'font-size: 7.5px; white-space: nowrap; letter-spacing: -0.3px;';
                } else if (len <= 20) {
                    return 'font-size: 6.8px; white-space: nowrap; letter-spacing: -0.4px;';
                } else {
                    return 'font-size: 6.2px; white-space: nowrap; letter-spacing: -0.5px;';
                }
            },

            formatTimeBangla(timeStr) {
                if (!timeStr) return '';
                const parts = timeStr.split(':');
                let h = parseInt(parts[0]);
                let m = parseInt(parts[1] || '0');
                let period = h >= 12 ? 'দুপুর' : 'সকাল';
                let displayHour = h % 12;
                if (displayHour === 0) displayHour = 12;
                let timeFormatted = `${String(displayHour).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
                return period + ' ' + this.toBanglaNum(timeFormatted);
            },

            formatTimeRangeBangla(start, end) {
                if (!start || !end) return '';
                return this.formatTimeBangla(start) + ' - ' + this.formatTimeBangla(end);
            },

            getPrintExamNameBengali() {
                let examObj = this.examsList ? this.examsList.find(e => e.id == this.printExamId) : null;
                let name = examObj ? examObj.name : (this.examText || '');
                if (name.includes('2nd Term') || name.includes('2nd')) return '২য় সাময়িক পরীক্ষা-২০২৬';
                if (name.includes('1st Term') || name.includes('1st')) return '১ম সাময়িক পরীক্ষা-২০২৬';
                if (name.includes('Annual') || name.includes('annual')) return 'বার্ষিক পরীক্ষা-২০২৬';
                return name || '২য় সাময়িক পরীক্ষা-২০২৬';
            },

            applyPrintPreset(preset) {
                let defaultShift1 = @json($shift1ClassIds);
                let defaultShift2 = @json($shift2ClassIds);

                if (preset === 'dual') {
                    this.printConfig.shift1.name = 'প্রথম শিফট';
                    this.printConfig.shift1.classRange = 'প্লে - ৪র্থ';
                    this.printConfig.shift1.timeLabel = 'সময় : সকাল ৯.০০ থেকে ১১.০০ টা';
                    this.printConfig.shift1.classes = [...defaultShift1];

                    this.printConfig.shift2.name = 'দ্বিতীয় শিফট';
                    this.printConfig.shift2.classRange = '৫ম - ১০ম';
                    this.printConfig.shift2.timeLabel = 'সময় : দুপুর ১২.০০ থেকে ০২.০০ টা';
                    this.printConfig.shift2.classes = [...defaultShift2];
                } else if (preset === 'shift1_dup') {
                    this.printConfig.shift1.name = 'প্রথম শিফট';
                    this.printConfig.shift1.classRange = 'প্লে - ৪র্থ';
                    this.printConfig.shift1.timeLabel = 'সময় : সকাল ৯.০০ থেকে ১১.০০ টা';
                    this.printConfig.shift1.classes = [...defaultShift1];

                    this.printConfig.shift2.name = 'প্রথম শিফট';
                    this.printConfig.shift2.classRange = 'প্লে - ৪র্থ';
                    this.printConfig.shift2.timeLabel = 'সময় : সকাল ৯.০০ থেকে ১১.০০ টা';
                    this.printConfig.shift2.classes = [...defaultShift1];
                } else if (preset === 'shift2_dup') {
                    this.printConfig.shift1.name = 'দ্বিতীয় শিফট';
                    this.printConfig.shift1.classRange = '৫ম - ১০ম';
                    this.printConfig.shift1.timeLabel = 'সময় : দুপুর ১২.০০ থেকে ০২.০০ টা';
                    this.printConfig.shift1.classes = [...defaultShift2];

                    this.printConfig.shift2.name = 'দ্বিতীয় শিফট';
                    this.printConfig.shift2.classRange = '৫ম - ১০ম';
                    this.printConfig.shift2.timeLabel = 'সময় : দুপুর ১২.০০ থেকে ০২.০০ টা';
                    this.printConfig.shift2.classes = [...defaultShift2];
                }
            },

            getPrintClassInfoBengali() {
                return 'শ্রেণী: ' + this.translateClass(this.classText);
            },

            formatDate(dateString) {
                if (!dateString) return '';
                const options = { day: '2-digit', month: 'short', year: 'numeric', weekday: 'long' };
                return new Date(dateString).toLocaleDateString('en-US', options);
            },
            
            async loadRoutine() {
                if (!this.form.class_id || !this.form.exam_id) {
                    this.noData = true;
                    this.routineSlots = [];
                    return;
                }
                
                this.printExamName = `${this.examText} - ${this.sessionText}`;
                this.printClassInfo = `Class: ${this.classText}`;
                this.loading = true;
                
                try {
                    const res = await axios.get('/exam-routine/get', { params: { 
                        session_year_id: this.form.session_year_id, 
                        exam_id: this.form.exam_id, 
                        class_id: this.form.class_id 
                    } });
                    this.routineSlots = res.data.routine;
                    this.noData = false;
                } catch (err) {
                    console.error(err);
                } finally {
                    this.loading = false;
                }
            },
            
            async saveSchedule() {
                if (!this.form.subject_id) {
                    showAlert('Please select Subject!', 'Validation');
                    return;
                }
                
                this.saving = true;
                try {
                    let res;
                    if (this.editMode) {
                        res = await axios.put(`/exam-routine/update/${this.editingSlotId}`, this.form, {
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                        });
                    } else {
                        res = await axios.post('/exam-routine/store', this.form, { 
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } 
                        });
                    }
                    
                    if (res.data.status === 'error') {
                        showModal('Error', res.data.message, 'danger');
                    } else {
                        showSuccess(res.data.message);
                        
                        // Reset input values
                        this.form.subject_id = '';
                        this.subjectText = 'Select Subject...';
                        this.form.room_number = '';
                        
                        if (this.editMode) {
                            this.cancelEdit();
                        }
                        
                        this.loadRoutine();
                    }
                } catch (err) {
                    let errMsg = 'System encountered an error. Please verify input fields.';
                    if (err.response && err.response.data && err.response.data.message) {
                        errMsg = err.response.data.message;
                    }
                    showModal('Error', errMsg, 'danger');
                } finally {
                    this.saving = false;
                }
            },
            
            startEditRoutine(slot) {
                this.editMode = true;
                this.editingSlotId = slot.id;
                this.form.session_year_id = slot.session_year_id;
                this.form.exam_id = slot.exam_id;
                this.form.class_id = slot.class_id;
                this.form.subject_id = slot.subject_id;
                this.form.exam_date = slot.exam_date;
                this.form.room_number = slot.room_number || '';
                this.form.start_time = slot.start_time.substring(0, 5);
                this.form.end_time = slot.end_time.substring(0, 5);
                
                // Resolve texts
                @foreach($classes as $c)
                    if (slot.class_id == '{{ $c->id }}') this.classText = '{{ $c->class_name }}';
                @endforeach
                @foreach($subjects as $s)
                    if (slot.subject_id == '{{ $s->id }}') this.subjectText = '{{ $s->subject_name ?? $s->name }}';
                @endforeach
            },
            
            cancelEdit() {
                this.editMode = false;
                this.editingSlotId = null;
                this.form.subject_id = '';
                this.subjectText = 'Select Subject...';
                this.form.room_number = '';
            },
            
            async deleteRoutine(id) {
                const confirmed = await showDanger('Delete Schedule Slot', 'Are you sure you want to delete this exam routine slot?');
                if (confirmed) {
                    try {
                        const res = await axios.delete(`/exam-routine/destroy/${id}`, { 
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } 
                        });
                        showSuccess(res.data.message);
                        this.loadRoutine();
                    } catch (err) {
                        let errMsg = 'Failed to delete routine slot.';
                        if (err.response && err.response.data && err.response.data.message) {
                            errMsg = err.response.data.message;
                        }
                        showModal('Error', errMsg, 'danger');
                    }
                }
            },

            async loadSavedRoutines() {
                this.loading = true;
                try {
                    const res = await axios.get('/exam-routine/saved');
                    this.savedRoutines = res.data.routines;
                } catch (err) {
                    console.error(err);
                } finally {
                    this.loading = false;
                }
            },

            viewSavedRoutine(routine) {
                this.form.session_year_id = routine.session_year_id;
                this.form.exam_id = routine.exam_id;
                this.sessionText = routine.session_name;
                this.examText = routine.exam_name;
                
                // Switch class selection to the first class that is part of this routine
                // to automatically populate the planner table
                const classIds = routine.classes.split(',').map(s => s.trim());
                if (classIds.length > 0) {
                    @foreach($classes as $c)
                        if ('{{ $c->class_name }}' === classIds[0]) {
                            this.form.class_id = '{{ $c->id }}';
                            this.classText = '{{ $c->class_name }}';
                        }
                    @endforeach
                }
                
                this.activeTab = 'planner';
                this.loadRoutine();
            },

            async deleteSavedRoutine(routine) {
                const confirmed = await showDanger('Delete Entire Exam Routine', `Are you sure you want to permanently delete all exam slots for ${routine.exam_name} (${routine.session_name})?`);
                if (confirmed) {
                    const doubleConfirmed = await showDanger('Confirm Permanent Deletion', 'This action cannot be undone and will delete all class schedules for this exam. Confirm?');
                    if (doubleConfirmed) {
                        try {
                            const res = await axios.post('/exam-routine/bulk-destroy', {
                                session_year_id: routine.session_year_id,
                                exam_id: routine.exam_id
                            }, {
                                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                            });
                            showSuccess(res.data.message);
                            this.loadSavedRoutines();
                            // Reset current loaded routine if it matched the deleted one
                            if (this.form.session_year_id == routine.session_year_id && this.form.exam_id == routine.exam_id) {
                                this.routineSlots = [];
                                this.noData = true;
                            }
                        } catch (err) {
                            let errMsg = 'Failed to delete routine.';
                            if (err.response && err.response.data && err.response.data.message) {
                                errMsg = err.response.data.message;
                            }
                            showModal('Error', errMsg, 'danger');
                        }
                    }
                }
            },

            openDualShiftModal() {
                if (!this.printSessionYearId) {
                    this.printSessionYearId = this.form.session_year_id || '{{ $sessions->first()->id ?? "" }}';
                }
                if (!this.printExamId) {
                    this.printExamId = this.form.exam_id || '{{ $exams->first()->id ?? "" }}';
                }
                this.showPrintModal = true;
            },

            printSingleRoutine() {
                document.body.classList.remove('print-dual');
                document.body.classList.add('print-single');
                setTimeout(() => {
                    window.print();
                    document.body.classList.remove('print-single');
                }, 100);
            },

            async generateAndPrintDual() {
                localStorage.setItem('dualShiftPrintConfig', JSON.stringify(this.printConfig));
                
                let classMap = {};
                @foreach($classes as $c)
                    classMap['{{ $c->id }}'] = '{{ $c->class_name }}';
                @endforeach

                this.loading = true;
                try {
                    // 1. ALWAYS Compile Overall Shift 1 & Shift 2 Routines (For Page 1 Front Side)
                    this.shift1ClassNames = this.printConfig.shift1.classes.map(id => classMap[id] || '');
                    this.shift2ClassNames = this.printConfig.shift2.classes.map(id => classMap[id] || '');

                    let shift1Slots = [];
                    if (this.printConfig.shift1.classes.length > 0) {
                        let promises = this.printConfig.shift1.classes.map(cid => 
                            axios.get('/exam-routine/get', { params: { 
                                session_year_id: this.printSessionYearId, 
                                exam_id: this.printExamId, 
                                class_id: cid 
                            } })
                        );
                        let results = await Promise.all(promises);
                        results.forEach(res => {
                            shift1Slots.push(...(res.data.routine || []));
                        });
                    }

                    let shift2Slots = [];
                    if (this.printConfig.shift2.classes.length > 0) {
                        let promises = this.printConfig.shift2.classes.map(cid => 
                            axios.get('/exam-routine/get', { params: { 
                                session_year_id: this.printSessionYearId, 
                                exam_id: this.printExamId, 
                                class_id: cid 
                            } })
                        );
                        let results = await Promise.all(promises);
                        results.forEach(res => {
                            shift2Slots.push(...(res.data.routine || []));
                        });
                    }

                    let shift1Dates = {};
                    shift1Slots.forEach(slot => {
                        let d = slot.exam_date;
                        if (!shift1Dates[d]) {
                            shift1Dates[d] = {
                                date: d,
                                formattedDate: new Date(d).toLocaleDateString('en-US', { day: '2-digit', month: '2-digit', year: 'numeric' }),
                                day: new Date(d).toLocaleDateString('en-US', { weekday: 'short' }),
                                subjects: {}
                            };
                        }
                        shift1Dates[d].subjects[slot.class_id] = slot.subject ? (slot.subject.subject_name || slot.subject.name) : '';
                    });
                    this.shift1Rows = Object.values(shift1Dates).sort((a,b) => a.date.localeCompare(b.date));

                    let shift2Dates = {};
                    shift2Slots.forEach(slot => {
                        let d = slot.exam_date;
                        if (!shift2Dates[d]) {
                            shift2Dates[d] = {
                                date: d,
                                formattedDate: new Date(d).toLocaleDateString('en-US', { day: '2-digit', month: '2-digit', year: 'numeric' }),
                                day: new Date(d).toLocaleDateString('en-US', { weekday: 'short' }),
                                subjects: {}
                            };
                        }
                        shift2Dates[d].subjects[slot.class_id] = slot.subject ? (slot.subject.subject_name || slot.subject.name) : '';
                    });
                    this.shift2Rows = Object.values(shift2Dates).sort((a,b) => a.date.localeCompare(b.date));

                    // 2. If Batch Class is selected, fetch students & batch class slots (For Page 2 Back Side)
                    if (this.batchClassId) {
                        this.batchClassName = classMap[this.batchClassId] || '';

                        let sRes = await axios.get('/exam-routine/students-by-class', { params: { class_id: this.batchClassId } });
                        this.batchStudents = sRes.data.students || [];

                        let rRes = await axios.get('/exam-routine/get', { params: { 
                            session_year_id: this.printSessionYearId, 
                            exam_id: this.printExamId, 
                            class_id: this.batchClassId 
                        } });
                        this.batchClassSlots = rRes.data.routine || [];
                    } else {
                        this.batchStudents = [];
                        this.batchClassSlots = [];
                    }

                    // 3. Trigger Print Window with automatic screen cleanup
                    
                    const cleanupPrintState = () => {
                        document.body.classList.remove('print-dual');
                        document.body.classList.remove('print-single');
                    };

                    window.addEventListener('afterprint', cleanupPrintState, { once: true });
                    
                    document.body.classList.remove('print-single');
                    document.body.classList.add('print-dual');
                    
                    setTimeout(() => {
                        window.print();
                        setTimeout(cleanupPrintState, 200);
                    }, 400);

                } catch (err) {
                    console.error(err);
                    showModal('Error', 'Failed to compile dual shift printable layout datasets.', 'danger');
                } finally {
                    this.loading = false;
                }
            }
        };
    }
</script>

@endpush

@section('content')
<div x-data="examRoutinePage()" class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Exam Routine
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Manage exam schedules, subject slots, timings, and print formal sheets</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="openDualShiftModal()" class="bg-gradient-to-r from-themeBlue to-themeGreen text-white px-5 py-3 rounded-xl font-bold text-xs uppercase tracking-widest transition-all shadow-md hover:-translate-y-0.5 hover:shadow-lg flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Dual Shift Print
            </button>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left Column: Add/Edit Exam Subject Form -->
        <div class="lg:col-span-4 no-print">
            <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
                <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-widest border-b border-gray-100 dark:border-white/[0.06] pb-4 mb-6">
                    <span x-text="editMode ? 'Edit Exam Subject' : 'Add Exam Subject'"></span>
                </h3>
                
                <form @submit.prevent="saveSchedule()">
                    <div class="space-y-4">
                        
                        <!-- Session & Exam Name Grid -->
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Session Select -->
                            <div class="relative" @click.away="if(dropdownOpen === 'session') dropdownOpen = null">
                                <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Session *</label>
                                <button type="button" @click="dropdownOpen = dropdownOpen === 'session' ? null : 'session'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                    <span class="truncate" x-text="sessionText"></span>
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="dropdownOpen === 'session'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                                    @foreach($sessions as $session)
                                        <button type="button" @click="selectSession('{{ $session->id }}', '{{ $session->session_name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.session_year_id == '{{ $session->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                            <span>{{ $session->session_name }}</span>
                                            <template x-if="form.session_year_id == '{{ $session->id }}'">
                                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            </template>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            
                            <!-- Exam Select -->
                            <div class="relative" @click.away="if(dropdownOpen === 'exam') dropdownOpen = null">
                                <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Exam Name *</label>
                                <button type="button" @click="dropdownOpen = dropdownOpen === 'exam' ? null : 'exam'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                    <span class="truncate" x-text="examText"></span>
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="dropdownOpen === 'exam'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                                    <button type="button" @click="selectExam('', 'Select Exam')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-455 transition-colors">Select Exam</button>
                                    @foreach($exams as $exam)
                                        <button type="button" @click="selectExam('{{ $exam->id }}', '{{ $exam->name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.exam_id == '{{ $exam->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                            <span>{{ $exam->name }}</span>
                                            <template x-if="form.exam_id == '{{ $exam->id }}'">
                                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            </template>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Class Select -->
                        <div class="relative" @click.away="if(dropdownOpen === 'class') dropdownOpen = null">
                            <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Class *</label>
                            <button type="button" @click="dropdownOpen = dropdownOpen === 'class' ? null : 'class'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                <span class="truncate" x-text="classText"></span>
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="dropdownOpen === 'class'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                                <button type="button" @click="selectClass('', 'Select Class...')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-455 transition-colors">Select Class...</button>
                                @foreach($classes as $class)
                                    <button type="button" @click="selectClass('{{ $class->id }}', '{{ $class->class_name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.class_id == '{{ $class->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                        <span>{{ $class->class_name }}</span>
                                        <template x-if="form.class_id == '{{ $class->id }}'">
                                            <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </template>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <!-- Subject Select -->
                        <div class="relative" @click.away="if(dropdownOpen === 'subject') dropdownOpen = null">
                            <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Subject *</label>
                            <button type="button" @click="dropdownOpen = dropdownOpen === 'subject' ? null : 'subject'" class="w-full h-11 px-3 bg-gray-55/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                <span class="truncate" x-text="subjectText"></span>
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="dropdownOpen === 'subject'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                                <button type="button" @click="selectSubject('', 'Select Subject...')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-455 transition-colors">Select Subject...</button>
                                <div x-show="!form.class_id" class="px-4 py-3 text-xs text-gray-455 dark:text-gray-400 italic text-center">
                                    Please select a class first
                                </div>
                                @foreach($subjects as $subject)
                                    <button type="button" x-show="form.class_id && form.class_id == '{{ $subject->class_id }}'" @click="selectSubject('{{ $subject->id }}', '{{ $subject->subject_name ?? $subject->name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.subject_id == '{{ $subject->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                        <span>{{ $subject->subject_name ?? $subject->name }}</span>
                                        <template x-if="form.subject_id == '{{ $subject->id }}'">
                                            <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </template>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Date & Room No -->
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Custom Date Picker -->
                            <div class="relative" x-data="datePicker(form.exam_date)" @date-selected="form.exam_date = $event.detail" @click.away="show = false">
                                <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Exam Date *</label>
                                <button type="button" @click="show = !show" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                    <span class="truncate" x-text="formatDisplay(value)"></span>
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </button>
                                <div x-show="show" x-cloak class="absolute z-50 w-64 mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3" x-transition>
                                    <div class="flex items-center justify-between mb-3">
                                        <button type="button" @click="prevMonth()" class="text-gray-400 hover:text-gray-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg></button>
                                        <div class="text-[10px] font-black text-gray-850 dark:text-gray-200 uppercase tracking-widest"><span x-text="monthNames[currentMonth]"></span> <span x-text="currentYear"></span></div>
                                        <button type="button" @click="nextMonth()" class="text-gray-400 hover:text-gray-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg></button>
                                    </div>
                                    <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-black text-gray-400 mb-2">
                                        <div>S</div><div>M</div><div>T</div><div>W</div><div>T</div><div>F</div><div>S</div>
                                    </div>
                                    <div class="grid grid-cols-7 gap-1">
                                        <template x-for="dayObj in days">
                                            <button type="button" @click="selectDay(dayObj.day)" class="aspect-square text-xs font-bold rounded-lg flex items-center justify-center transition-colors" :class="dayObj.day ? (value === `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(dayObj.day).padStart(2, '0')}` ? 'bg-themeBlue text-white font-black' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-105 dark:hover:bg-gray-800') : 'pointer-events-none opacity-0'" x-text="dayObj.day || ''"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <!-- Room No -->
                            <div>
                                <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Room No (Opt)</label>
                                <input type="text" name="room_number" x-model="form.room_number" placeholder="Ex: 101" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                            </div>
                        </div>

                        <!-- Start & End Time -->
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Start Time Custom Picker -->
                            <div class="relative" x-data="timePicker(form.start_time)" @time-selected="form.start_time = $event.detail" @click.away="show = false">
                                <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Start Time *</label>
                                <button type="button" @click="show = !show" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                    <span class="truncate" x-text="formatDisplay(value)"></span>
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </button>
                                <div x-show="show" x-cloak class="absolute z-50 w-48 mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3" x-transition>
                                    <div class="flex gap-2 justify-center items-center">
                                        <div class="flex flex-col items-center">
                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">HR</span>
                                            <select :value="hour" @change="selectHour(parseInt($event.target.value))" class="bg-gray-50 dark:bg-themeDark border border-gray-200 dark:border-gray-800 rounded-lg p-1 text-xs font-bold text-gray-750 dark:text-gray-200 focus:outline-none focus:border-themeBlue">
                                                <template x-for="h in [12,1,2,3,4,5,6,7,8,9,10,11]"><option :value="h" x-text="h"></option></template>
                                            </select>
                                        </div>
                                        <div class="flex flex-col items-center">
                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">MIN</span>
                                            <select :value="minute" @change="selectMinute(parseInt($event.target.value))" class="bg-gray-50 dark:bg-themeDark border border-gray-200 dark:border-gray-800 rounded-lg p-1 text-xs font-bold text-gray-750 dark:text-gray-200 focus:outline-none focus:border-themeBlue">
                                                <template x-for="m in [0,5,10,15,20,25,30,35,40,45,50,55]"><option :value="m" x-text="String(m).padStart(2, '0')"></option></template>
                                            </select>
                                        </div>
                                        <div class="flex flex-col items-center">
                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">AM/PM</span>
                                            <select :value="period" @change="selectPeriod($event.target.value)" class="bg-gray-50 dark:bg-themeDark border border-gray-200 dark:border-gray-800 rounded-lg p-1 text-xs font-bold text-gray-750 dark:text-gray-200 focus:outline-none focus:border-themeBlue">
                                                <option value="AM">AM</option><option value="PM">PM</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- End Time Custom Picker -->
                            <div class="relative" x-data="timePicker(form.end_time)" @time-selected="form.end_time = $event.detail" @click.away="show = false">
                                <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">End Time *</label>
                                <button type="button" @click="show = !show" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                    <span class="truncate" x-text="formatDisplay(value)"></span>
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </button>
                                <div x-show="show" x-cloak class="absolute z-50 w-48 mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3" x-transition>
                                    <div class="flex gap-2 justify-center items-center">
                                        <div class="flex flex-col items-center">
                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">HR</span>
                                            <select :value="hour" @change="selectHour(parseInt($event.target.value))" class="bg-gray-50 dark:bg-themeDark border border-gray-200 dark:border-gray-800 rounded-lg p-1 text-xs font-bold text-gray-755 dark:text-gray-200 focus:outline-none focus:border-themeBlue">
                                                <template x-for="h in [12,1,2,3,4,5,6,7,8,9,10,11]"><option :value="h" x-text="h"></option></template>
                                            </select>
                                        </div>
                                        <div class="flex flex-col items-center">
                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">MIN</span>
                                            <select :value="minute" @change="selectMinute(parseInt($event.target.value))" class="bg-gray-50 dark:bg-themeDark border border-gray-200 dark:border-gray-800 rounded-lg p-1 text-xs font-bold text-gray-755 dark:text-gray-200 focus:outline-none focus:border-themeBlue">
                                                <template x-for="m in [0,5,10,15,20,25,30,35,40,45,50,55]"><option :value="m" x-text="String(m).padStart(2, '0')"></option></template>
                                            </select>
                                        </div>
                                        <div class="flex flex-col items-center">
                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">AM/PM</span>
                                            <select :value="period" @change="selectPeriod($event.target.value)" class="bg-gray-50 dark:bg-themeDark border border-gray-200 dark:border-gray-800 rounded-lg p-1 text-xs font-bold text-gray-755 dark:text-gray-200 focus:outline-none focus:border-themeBlue">
                                                <option value="AM">AM</option><option value="PM">PM</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-3">
                            <button type="submit" :disabled="saving" class="flex-grow bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black py-4 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all uppercase tracking-widest text-xs active:scale-95 flex items-center justify-center gap-2">
                                <span x-text="saving ? 'Saving...' : (editMode ? 'Update Schedule' : '+ Add to Schedule')"></span>
                            </button>
                            <button x-show="editMode" type="button" @click="cancelEdit()" class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-800 dark:text-white font-black py-4 px-6 rounded-xl text-xs uppercase tracking-widest transition-all">
                                Cancel
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column: Exam Routine Schedule Viewer Sheet & Saved Routines -->
        <div class="lg:col-span-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 no-print">
                <!-- Tab Headers -->
                <div class="flex bg-gray-100 dark:bg-themeDark p-1 rounded-2xl border border-gray-200/50 dark:border-white/[0.04]">
                    <button type="button" @click="activeTab = 'planner'" class="px-5 py-2 text-xs font-black uppercase tracking-wider rounded-xl transition-all" :class="activeTab === 'planner' ? 'bg-white dark:bg-themeNavy text-themeBlue shadow-sm' : 'text-gray-500 hover:text-gray-800 dark:hover:text-gray-200'">
                        Routine Planner
                    </button>
                    <button type="button" @click="activeTab = 'saved'; loadSavedRoutines();" class="px-5 py-2 text-xs font-black uppercase tracking-wider rounded-xl transition-all" :class="activeTab === 'saved' ? 'bg-white dark:bg-themeNavy text-themeBlue shadow-sm' : 'text-gray-500 hover:text-gray-800 dark:hover:text-gray-200'">
                        Saved Routines
                    </button>
                </div>
                
                <div class="flex items-center gap-2">
                    <span x-show="loading" class="text-xs font-bold text-gray-555 uppercase animate-pulse">Syncing...</span>
                    <button x-show="activeTab === 'planner'" type="button" @click="printSingleRoutine()" class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-800 dark:text-white px-6 py-3 rounded-xl font-bold text-xs uppercase tracking-widest transition-all shadow-md flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print Schedule
                    </button>
                </div>
            </div>

            <!-- Tab 1: Routine Planner Content -->
            <div x-show="activeTab === 'planner'" class="space-y-6">
                <!-- Placeholder select information -->
                <div x-show="noData" class="text-center py-20 bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl no-print">
                    <h4 class="text-xs font-black text-gray-400 dark:text-gray-550 uppercase tracking-widest">Select Details</h4>
                    <p class="text-xs text-gray-450 dark:text-gray-500 font-bold mt-2">Choose Session, Exam & Class to view the schedule.</p>
                </div>

                <!-- Routine Board table sheet -->
                <div x-show="!noData" class="bg-white dark:bg-themeNavy rounded-3xl p-6 border border-gray-100 dark:border-white/[0.06] shadow-sm overflow-x-auto" id="printableRoutine">
                    
                    <!-- School print header -->
                    <div class="school-header hidden print:block mb-6">
                        <h1 style="font-family: 'Onest', sans-serif; font-weight: 900;">ম্যাকস স্কুল এন্ড কলেজ</h1>
                        <h3 style="font-family: 'Onest', sans-serif; font-weight: bold;" x-text="getPrintExamNameBengali()">২য় সাময়িক পরীক্ষা-২০২৬</h3>
                        <p style="font-family: 'Onest', sans-serif; font-weight: bold;" x-text="getPrintClassInfoBengali()">শ্রেণী: পঞ্চম</p>
                    </div>

                    <div class="table-container bg-transparent !border-none !shadow-none !mt-0 !mb-0">
                        <table class="w-full text-left border-collapse table">
                            <thead>
                                <tr class="!bg-transparent">
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">তারিখ ও বার</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">বিষয়</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">সময়</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">কক্ষ</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-center no-print w-24">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-150 dark:divide-white/[0.06]">
                                <template x-for="slot in routineSlots" :key="slot.id">
                                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                                        <td class="py-0 px-0">
                                            <span class="font-bold text-gray-900 dark:text-gray-100 text-sm" x-text="`${formatDateBangla(slot.exam_date)} - ${getDayBangla(slot.exam_date)}`"></span>
                                        </td>
                                        <td class="py-0 px-0">
                                            <span class="font-bold text-gray-900 dark:text-gray-100 text-sm" x-text="translateSubject(slot.subject ? (slot.subject.subject_name || slot.subject.name) : 'N/A')"></span>
                                        </td>
                                        <td class="py-0 px-0">
                                            <span class="inline-block px-2.5 py-1 text-[10px] font-bold text-themeBlue bg-themeBlue/10 rounded-lg" x-text="formatTimeRangeBangla(slot.start_time, slot.end_time)"></span>
                                        </td>
                                        <td class="py-0 px-0 text-sm font-bold text-gray-600 dark:text-gray-400" x-text="toBanglaNum(slot.room_number) || 'নির্ধারিত হয়নি'"></td>
                                        <td class="py-0 px-0 text-center no-print">
                                            <div class="flex items-center justify-center gap-2">
                                                <button type="button" @click="startEditRoutine(slot)" class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue" title="Edit Slot">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>
                                                <button type="button" @click="deleteRoutine(slot.id)" class="action-btn text-red-600 hover:text-red-800 hover:border-red-600" title="Delete Slot">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="routineSlots.length === 0">
                                    <tr>
                                        <td colspan="5" class="py-12 text-center text-gray-400 font-bold uppercase tracking-wider">No exams scheduled yet</td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Saved Routines Content -->
            <div x-show="activeTab === 'saved'" class="space-y-6" x-cloak>
                <div class="bg-white dark:bg-themeNavy rounded-3xl p-6 border border-gray-100 dark:border-white/[0.06] shadow-sm overflow-x-auto animate-fadeIn">
                    <div class="table-container bg-transparent !border-none !shadow-none !mt-0 !mb-0">
                        <table class="w-full text-left border-collapse table">
                            <thead>
                                <tr class="!bg-transparent">
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Session</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Exam Name</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Scheduled Classes</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Date Range</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-center w-36">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-150 dark:divide-white/[0.06]">
                                <template x-for="r in savedRoutines" :key="`${r.session_year_id}-${r.exam_id}`">
                                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                                        <td class="py-0 px-0">
                                            <span class="font-mono font-black text-gray-700 dark:text-gray-300 text-sm" x-text="r.session_name"></span>
                                        </td>
                                        <td class="py-0 px-0">
                                            <span class="font-bold text-gray-900 dark:text-gray-100 text-sm" x-text="r.exam_name"></span>
                                        </td>
                                        <td class="py-0 px-0 text-sm font-semibold text-gray-650 dark:text-gray-450" x-text="r.classes || 'N/A'"></td>
                                        <td class="py-0 px-0 text-sm font-bold text-gray-650 dark:text-gray-450 font-mono" x-text="r.date_range"></td>
                                        <td class="py-0 px-0 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button type="button" @click="viewSavedRoutine(r)" class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue" title="View/Edit Planner">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                </button>
                                                <button type="button" @click="deleteSavedRoutine(r)" class="action-btn text-red-600 hover:text-red-800 hover:border-red-600" title="Delete Full Routine">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="savedRoutines.length === 0">
                                    <tr>
                                        <td colspan="5" class="py-12 text-center text-gray-400 font-bold uppercase tracking-wider">No routines created yet</td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    <!-- Dual Shift Print Configuration Modal -->
    <div x-show="showPrintModal" x-cloak class="fixed inset-0 z-[99999] overflow-y-auto no-print flex items-center justify-center p-4 bg-black/40 backdrop-blur-md" x-transition>
        <div class="bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-3xl p-6 w-full max-w-4xl shadow-2xl relative">
            <h3 class="text-lg font-black text-gray-800 dark:text-white uppercase tracking-wider border-b border-gray-100 dark:border-white/[0.06] pb-3 mb-4">
                Dual Shift Print Setup
            </h3>
            
            <!-- Session & Exam Selection Section (Decoupled from left form) -->
            <div class="mb-5 p-4 bg-gray-50/50 dark:bg-themeNavy/45 border border-gray-100 dark:border-gray-800 rounded-2xl space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Session Year *</label>
                        <select x-model="printSessionYearId" class="w-full h-11 px-3 bg-white dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold focus:outline-none focus:border-themeBlue">
                            @foreach($sessions as $s)
                                <option value="{{ $s->id }}">{{ $s->session_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Exam Name *</label>
                        <select x-model="printExamId" class="w-full h-11 px-3 bg-white dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold focus:outline-none focus:border-themeBlue">
                            @foreach($exams as $e)
                                <option value="{{ $e->id }}">{{ $e->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Student Info Fill (Optional)</label>
                        <select x-model="batchClassId" class="w-full h-11 px-3 bg-white dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold focus:outline-none focus:border-themeBlue">
                            <option value="">All Classes (Blank Template)</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}">{{ $c->class_name }} (Pre-fill Batch)</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Preset Mode Toggle Buttons -->
                <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-gray-200/60 dark:border-gray-800">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-wider mr-1">Layout Presets:</span>
                    <button type="button" @click="applyPrintPreset('dual')" class="px-3 py-1.5 bg-indigo-50 dark:bg-themeBlue/20 text-themeBlue font-extrabold text-[11px] rounded-lg border border-themeBlue/30 hover:bg-themeBlue hover:text-white transition-all">
                        🌗 Dual Shift (Shift 1 + Shift 2)
                    </button>
                    <button type="button" @click="applyPrintPreset('shift1_dup')" class="px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 font-extrabold text-[11px] rounded-lg border border-blue-200 dark:border-blue-800 hover:bg-blue-600 hover:text-white transition-all">
                        ☀️ Shift 1 Duplicate (Top & Bottom Same)
                    </button>
                    <button type="button" @click="applyPrintPreset('shift2_dup')" class="px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 font-extrabold text-[11px] rounded-lg border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-600 hover:text-white transition-all">
                        🌙 Shift 2 Duplicate (Top & Bottom Same)
                    </button>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-h-[50vh] overflow-y-auto pr-2">
                <!-- Left: Shift 1 Config -->
                <div class="space-y-4 p-4 bg-blue-50/15 dark:bg-themeBlue/[0.02] border border-themeBlue/10 rounded-2xl">
                    <h4 class="text-xs font-black text-themeBlue uppercase tracking-widest mb-2 border-b border-themeBlue/15 pb-2">Shift 1 (Top Half)</h4>
                    <div>
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1 ml-1">Shift Name</label>
                        <select x-model="printConfig.shift1.name" class="w-full h-10 px-3 bg-white dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold focus:outline-none focus:border-themeBlue">
                            <option value="প্রথম শিফট">প্রথম শিফট</option>
                            <option value="দ্বিতীয় শিফট">দ্বিতীয় শিফট</option>
                            <option value="একক শিফট">একক শিফট</option>
                            <option value="প্রভাতী শিফট">প্রভাতী শিফট</option>
                            <option value="দিবা শিফট">দিবা শিফট</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1 ml-1">Classes Range Label</label>
                        <select x-model="printConfig.shift1.classRange" class="w-full h-10 px-3 bg-white dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold focus:outline-none focus:border-themeBlue">
                            <option value="প্লে - ৪র্থ">প্লে - ৪র্থ</option>
                            <option value="৫ম - ১০ম">৫ম - ১০ম</option>
                            <option value="প্লে - ১০ম">প্লে - ১০ম</option>
                            <option value="১ম - ৫ম">১ম - ৫ম</option>
                            <option value="৬ষ্ঠ - ১০ম">৬ষ্ঠ - ১০ম</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1 ml-1">Timing Label</label>
                        <select x-model="printConfig.shift1.timeLabel" class="w-full h-10 px-3 bg-white dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold focus:outline-none focus:border-themeBlue">
                            <option value="সময় : সকাল ৯.০০ থেকে ১১.০০ টা">সময় : সকাল ৯.০০ থেকে ১১.০০ টা</option>
                            <option value="সময় : দুপুর ১২.০০ থেকে ০২.০০ টা">সময় : দুপুর ১২.০০ থেকে ০২.০০ টা</option>
                            <option value="সময় : সকাল ১০.০০ থেকে ০১.০০ টা">সময় : সকাল ১০.০০ থেকে ০১.০০ টা</option>
                            <option value="সময় : দুপুর ০২.০০ থেকে ০৫.০০ টা">সময় : দুপুর ০২.০০ থেকে ০৫.০০ টা</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1 ml-1">Footnote Note (Optional)</label>
                        <input type="text" x-model="printConfig.shift1.footnote" class="w-full h-10 px-3 bg-white dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold focus:outline-none focus:border-themeBlue">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-2 ml-1">Include Classes</label>
                        <div class="grid grid-cols-2 gap-2 bg-white dark:bg-themeDark border border-gray-100 dark:border-gray-800 rounded-xl p-3 max-h-36 overflow-y-auto">
                            @foreach($classes as $c)
                                <label class="flex items-center gap-2 text-xs font-bold text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input type="checkbox" value="{{ $c->id }}" x-model="printConfig.shift1.classes" class="rounded text-themeBlue focus:ring-themeBlue/30">
                                    <span>{{ $c->class_name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Right: Shift 2 Config -->
                <div class="space-y-4 p-4 bg-green-50/15 dark:bg-themeGreen/[0.02] border border-themeGreen/10 rounded-2xl">
                    <h4 class="text-xs font-black text-themeGreen uppercase tracking-widest mb-2 border-b border-themeGreen/15 pb-2">Shift 2 (Bottom Half)</h4>
                    <div>
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1 ml-1">Shift Name</label>
                        <select x-model="printConfig.shift2.name" class="w-full h-10 px-3 bg-white dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold focus:outline-none focus:border-themeBlue">
                            <option value="দ্বিতীয় শিফট">দ্বিতীয় শিফট</option>
                            <option value="প্রথম শিফট">প্রথম শিফট</option>
                            <option value="একক শিফট">একক শিফট</option>
                            <option value="প্রভাতী শিফট">প্রভাতী শিফট</option>
                            <option value="দিবা শিফট">দিবা শিফট</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1 ml-1">Classes Range Label</label>
                        <select x-model="printConfig.shift2.classRange" class="w-full h-10 px-3 bg-white dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold focus:outline-none focus:border-themeBlue">
                            <option value="৫ম - ১০ম">৫ম - ১০ম</option>
                            <option value="প্লে - ৪র্থ">প্লে - ৪র্থ</option>
                            <option value="প্লে - ১০ম">প্লে - ১০ম</option>
                            <option value="১ম - ৫ম">১ম - ৫ম</option>
                            <option value="৬ষ্ঠ - ১০ম">৬ষ্ঠ - ১০ম</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1 ml-1">Timing Label</label>
                        <select x-model="printConfig.shift2.timeLabel" class="w-full h-10 px-3 bg-white dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold focus:outline-none focus:border-themeBlue">
                            <option value="সময় : দুপুর ১২.০০ থেকে ০২.০০ টা">সময় : দুপুর ১২.০০ থেকে ০২.০০ টা</option>
                            <option value="সময় : সকাল ৯.০০ থেকে ১১.০০ টা">সময় : সকাল ৯.০০ থেকে ১১.০০ টা</option>
                            <option value="সময় : সকাল ১০.০০ থেকে ০১.০০ টা">সময় : সকাল ১০.০০ থেকে ০১.০০ টা</option>
                            <option value="সময় : দুপুর ০২.০০ থেকে ০৫.০০ টা">সময় : দুপুর ০২.০০ থেকে ০৫.০০ টা</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1 ml-1">Footnote Note (Optional)</label>
                        <input type="text" x-model="printConfig.shift2.footnote" class="w-full h-10 px-3 bg-white dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold focus:outline-none focus:border-themeBlue">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-2 ml-1">Include Classes</label>
                        <div class="grid grid-cols-2 gap-2 bg-white dark:bg-themeDark border border-gray-100 dark:border-gray-800 rounded-xl p-3 max-h-36 overflow-y-auto">
                            @foreach($classes as $c)
                                <label class="flex items-center gap-2 text-xs font-bold text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input type="checkbox" value="{{ $c->id }}" x-model="printConfig.shift2.classes" class="rounded text-themeBlue focus:ring-themeBlue/30">
                                    <span>{{ $c->class_name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Bottom Full-Width: Announcement Block Config -->
                <div class="md:col-span-2 space-y-4 p-4 bg-gray-50 dark:bg-themeNavy border border-gray-200 dark:border-white/[0.06] rounded-2xl">
                    <h4 class="text-xs font-black text-gray-800 dark:text-white uppercase tracking-widest mb-2 border-b border-gray-200 dark:border-white/[0.06] pb-2">Parent Announcement Flyer (Back Side)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1 ml-1">Announcement Title</label>
                            <input type="text" x-model="printConfig.announcement.title" class="w-full h-10 px-3 bg-white dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold focus:outline-none focus:border-themeBlue">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1 ml-1">Dues Payment Limit Date</label>
                            <input type="text" x-model="printConfig.announcement.dueLimit" class="w-full h-10 px-3 bg-white dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold focus:outline-none focus:border-themeBlue">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1 ml-1">Announcement Text</label>
                        <textarea x-model="printConfig.announcement.text" rows="3" class="w-full p-3 bg-white dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold focus:outline-none focus:border-themeBlue"></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1 ml-1">Principal Seal Title</label>
                            <input type="text" x-model="printConfig.announcement.principalTitle" class="w-full h-10 px-3 bg-white dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold focus:outline-none focus:border-themeBlue">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1 ml-1">Principal Name / Seal Details</label>
                            <input type="text" x-model="printConfig.announcement.principalName" class="w-full h-10 px-3 bg-white dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold focus:outline-none focus:border-themeBlue">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1 ml-1">Contact Phone</label>
                            <input type="text" x-model="printConfig.announcement.phone" class="w-full h-10 px-3 bg-white dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold focus:outline-none focus:border-themeBlue">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6 border-t border-gray-100 dark:border-white/[0.06] pt-4">
                <button type="button" @click="showPrintModal = false" class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold px-6 py-3 rounded-xl text-xs uppercase tracking-widest transition-all">
                    Close
                </button>
                <button type="button" @click="generateAndPrintDual()" class="bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black px-6 py-3 rounded-xl shadow-lg hover:shadow-xl transition-all uppercase tracking-widest text-xs">
                    Print Now
                </button>
            </div>
        </div>
    </div>

    <!-- Dual Shift Print Only Container -->
    <div id="dualShiftPrintLayout" class="text-black bg-white" style="font-family: 'Noto Serif Bengali', serif;">
        
        <!-- BLANK MODE (When no class is selected or class has no students) -->
        <template x-if="!batchClassId || batchStudents.length === 0">
            <div>
                <!-- PAGE 1: ROUTINES FRONT PAGE -->
                <div class="print-page flex flex-col justify-between" style="height: 282mm !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; padding: 0; box-sizing: border-box; background-color: #fff !important;">
                    
                    <!-- SHIFT 1 TABLE (TOP HALF) -->
                    <div style="height: 133mm !important; display: flex !important; flex-direction: column !important; box-sizing: border-box; background-color: #fff !important;">
                        <!-- Header -->
                        <div class="text-center" style="margin-top: 1mm; margin-bottom: 1.5mm;">
                            <h2 class="text-xl font-black" style="margin: 0 0 2px 0; color: #000; font-size: 22px;">ম্যাকস স্কুল এন্ড কলেজ</h2>
                            <h3 class="text-xs font-extrabold" style="margin: 2px 0; font-size: 16px;" x-text="getPrintExamNameBengali()">২য় সাময়িক পরীক্ষা-২০২৬</h3>
                            <div style="display: inline-block; border: 1.5px solid #000; border-radius: 3px; padding: 0px 10px; font-size: 18px; font-weight: 900; margin-top: 2px; background-color: #f3f4f6; -webkit-print-color-adjust: exact; print-color-adjust: exact;">রুটিন</div>
                        </div>
                        
                        <!-- Meta Info Row -->
                        <div class="flex justify-between items-center text-xs font-extrabold" style="margin-bottom: 1mm;">

                            <!-- Left Side: Grouping First Two Divs -->
                            <div class="flex items-center gap-3">
                                <div style="border: 1.5px solid #000; border-radius: 3px; padding: 2px 6px; font-weight: 800; font-size: 14px;" x-text="printConfig.shift1.name">প্রথম শিফট</div>
                                <div style="border: 1.5px solid #000; border-radius: 3px; padding: 2px 8px; font-weight: 800; font-size: 14px;" x-text="printConfig.shift1.classRange">প্লে - ৪র্থ</div>
                            </div>

                            <!-- Right Side: The Third Div -->
                            <div style="border: 1.5px solid #000; border-radius: 3px; padding: 2px 6px; font-weight: 800; font-size: 14px;" x-text="printConfig.shift1.timeLabel">সময় : সকাল ৯.০০ থেকে ১১.০০ টা</div>

                        </div>
                        
                        <!-- Table -->
                        <table style="width: 100%; border-collapse: collapse; border: 1.5px solid #000; font-size: 9px; line-height: 0.3;">
                            <thead>
                                <tr class="routine-header-tr">
                                    <th class="routine-th" style="border: 1px solid #000; padding: 0px 0px; font-weight: bold; font-size: 10px; text-align: center; background-color: #d1d5db !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; width: 14%;">তারিখ</th>
                                    <th class="routine-th" style="border: 1px solid #000; padding: 0px 0px; font-weight: bold; font-size: 10px; text-align: center; background-color: #d1d5db !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; width: 14%;">বার</th>
                                    <template x-for="clsName in shift1ClassNames">
                                        <th class="routine-th" style="border: 1px solid #000; padding: 0px 0px; font-weight: bold; font-size: 10px; text-align: center; background-color: #d1d5db !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important;" x-text="translateClass(clsName)"></th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="row in shift1Rows">
                                    <tr>
                                        <td style="border: 1px solid #000; padding: 0.5px 0px; font-weight: bold; text-align: center; font-size: 9px;" x-text="formatDateBangla(row.date)"></td>
                                        <td style="border: 1px solid #000; padding: 0.5px 0px; font-weight: bold; text-align: center; font-size: 9px;" x-text="getDayBangla(row.date)"></td>
                                        <template x-for="clsId in printConfig.shift1.classes">
                                            <td style="border: 1px solid #000; padding: 0.5px 0px; text-align: center; font-weight: bold;" :style="getSubjectStyle(row.subjects[clsId])" x-text="translateSubject(row.subjects[clsId])"></td>
                                        </template>
                                    </tr>
                                </template>
                            </tbody>
                        </table>

                        <!-- Spacer to push footnote to bottom -->
                        <div style="flex: 1;"></div>

                        <!-- Footnote -->
                        <div class="text-center text-[14px] font-bold" x-show="printConfig.shift1.footnote" x-text="printConfig.shift1.footnote" style="margin-top: 2mm; margin-bottom: 1mm;"></div>
                    </div>

                    <!-- SHIFT 2 TABLE (BOTTOM HALF) -->
                    <div style="height: 133mm !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; padding-top: 4mm !important; box-sizing: border-box; background-color: #fff !important;">
                        <div>
                            <!-- Header -->
                            <div class="text-center" style="margin-top: 1mm; margin-bottom: 1.5mm;">
                                <h2 class="text-xl font-black" style="margin: 0 0 2px 0; color: #000; font-size: 22px;">ম্যাকস স্কুল এন্ড কলেজ</h2>
                                <h3 class="text-xs font-extrabold" style="margin: 2px 0; font-size: 16px;" x-text="getPrintExamNameBengali()">২য় সাময়িক পরীক্ষা-২০২৬</h3>
                                <div style="display: inline-block; border: 1.5px solid #000; border-radius: 3px; padding: 0px 10px; font-size: 18px; font-weight: 900; margin-top: 2px; background-color: #f3f4f6; -webkit-print-color-adjust: exact; print-color-adjust: exact;">রুটিন</div>
                            </div>
                            
                            <!-- Meta Info Row -->
                            <div class="flex justify-between items-center text-xs font-extrabold" style="margin-bottom: 1mm;">
    
                                <!-- Left Side: Grouping First Two Divs -->
                                <div class="flex items-center gap-3">
                                    <div style="border: 1.5px solid #000; border-radius: 3px; padding: 2px 6px; font-weight: 800; font-size: 14px;" x-text="printConfig.shift2.name">দ্বিতীয় শিফট</div>
                                    <div style="border: 1.5px solid #000; border-radius: 3px; padding: 2px 8px; font-weight: 800; font-size: 14px;" x-text="printConfig.shift2.classRange">৫ম - ৯ম</div>
                                </div>

                                <!-- Right Side: The Third Div -->
                                <div style="border: 1.5px solid #000; border-radius: 3px; padding: 2px 6px; font-weight: 800; font-size: 14px;" x-text="printConfig.shift2.timeLabel">সময় : দুপুর ১২.০০ থেকে ০২.০০ টা</div>

                            </div>
                            
                            <!-- Table -->
                            <table style="width: 100%; border-collapse: collapse; border: 1.5px solid #000; font-size: 9px; line-height: 0.3;">
                                <thead>
                                    <tr class="routine-header-tr">
                                        <th class="routine-th" style="border: 1px solid #000; padding: 0px 0px; font-weight: bold; font-size: 10px; text-align: center; background-color: #d1d5db !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; width: 14%;">তারিখ</th>
                                        <th class="routine-th" style="border: 1px solid #000; padding: 0px 0px; font-weight: bold; font-size: 10px; text-align: center; background-color: #d1d5db !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; width: 14%;">বার</th>
                                        <template x-for="clsName in shift2ClassNames">
                                            <th class="routine-th" style="border: 1px solid #000; padding: 0px 0px; font-weight: bold; font-size: 10px; text-align: center; background-color: #d1d5db !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important;" x-text="translateClass(clsName)"></th>
                                        </template>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="row in shift2Rows">
                                        <tr>
                                            <td style="border: 1px solid #000; padding: 0.5px 0px; font-weight: bold; text-align: center; font-size: 9px;" x-text="formatDateBangla(row.date)"></td>
                                            <td style="border: 1px solid #000; padding: 0.5px 0px; font-weight: bold; text-align: center; font-size: 9px;" x-text="getDayBangla(row.date)"></td>
                                            <template x-for="clsId in printConfig.shift2.classes">
                                                <td style="border: 1px solid #000; padding: 0.5px 0px; text-align: center; font-weight: bold;" :style="getSubjectStyle(row.subjects[clsId])" x-text="translateSubject(row.subjects[clsId])"></td>
                                            </template>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>

                            <!-- Footnote -->
                            <div class="text-center text-[14px] font-bold" x-show="printConfig.shift2.footnote" x-text="printConfig.shift2.footnote" style="margin-top: 2mm;"></div>
                        </div>
                    </div>

                <div class="page-break"></div>

                <!-- PAGE 2: PARENT FLYER BACK PAGE (BLANK TEMPLATE) -->
                <div class="print-page flex flex-col justify-between" style="height: 282mm !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; padding: 1mm 0; box-sizing: border-box; background-color: #fff !important;">
                    
                    <!-- FLYER TOP HALF -->
                    <div class="flyer-half" style="height: 138mm !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; border-bottom: 2px dashed #000; padding: 2mm 5mm 6mm 5mm; box-sizing: border-box; background-color: #fff !important;">
                        <div class="text-center" style="margin-bottom: 1.5mm;">
                            <p class="font-extrabold uppercase text-[16px]" style="margin: 0;">বিসমিল্লাহির রাহমানির রাহিম</p>
                            <h3 class="text-xl font-black" style="margin: 1px 0 0 0; color: #000;">ম্যাকস স্কুল এন্ড কলেজ</h3>
                            <p class="text-[16px] font-bold" style="margin: 1px 0 0 0;" x-text="`${printConfig.announcement.title} নোটিশ`"></p>
                        </div>
                        
                        <div class="font-bold" style="padding: 0 5px;">
                            <p style="margin: 0; font-size: 16px;">সম্মানিত অভিভাবক ও সুপ্রিয় শিক্ষার্থী,</p>
                            <p class="text-justify font-semibold" style="text-indent: 1.5em; margin: 2px 0 0 0; font-size: 16px;" x-text="printConfig.announcement.text"></p>
                        </div>

                        <!-- Right Signatures Area -->
                        <div class="flex justify-end" style="padding-right: 15px; margin-top: 4mm;">
                            <div class="text-center" style="line-height: 1.2;">
                                <p class="font-extrabold text-[16px]" style="margin: 0;" x-text="printConfig.announcement.principalName || 'মা-আসসালাম'"></p>
                                <div style="height: 5mm;"></div>
                                <p class="font-black text-[16px]" style="margin: 0;" x-text="printConfig.announcement.principalTitle || 'অধ্যক্ষ'"></p>
                                <p class="font-bold text-[16px]" style="margin: 0;">ম্যাকস স্কুল এন্ড কলেজ</p>
                            </div>
                        </div>

                        <!-- Student Info Blanks -->
                        <div class="font-bold text-sm" style="padding: 0 5px; margin-top: 4mm;">
                            <p style="margin: 0;">ছাত্র/ছাত্রীর নাম :.................................................................শ্রেণি :....................শাখা :..............রোল :....................</p>
                        </div>

                        <!-- Student Fee Box & Details (Directly Below Student Info) -->
                        <div class="grid grid-cols-12 gap-2 items-end" style="padding: 0 5px; margin-top: 2.5mm;">
                            <!-- Left table -->
                            <div class="col-span-5">
                                <table style="width: 100%; border-collapse: collapse; border: 1.5px solid #000; font-size: 10px; line-height: 0.8;">
                                    <tbody>
                                        <tr>
                                            <td style="border: 1px solid #000; padding: 2px 4px; font-weight: bold; width: 45%; text-align: left; background-color: #fff;">বেতন</td>
                                            <td style="border: 1px solid #000; padding: 2px 4px; width: 55%; text-align: center; font-weight: bold;" x-text="batchTuitionFee ? toBanglaNum(batchTuitionFee) : ''"></td>
                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid #000; padding: 2px 4px; font-weight: bold; text-align: left; background-color: #fff;">পরীক্ষা ফি</td>
                                            <td style="border: 1px solid #000; padding: 2px 4px; text-align: center; font-weight: bold;" x-text="batchExamFee ? toBanglaNum(batchExamFee) : ''"></td>
                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid #000; padding: 2px 4px; font-weight: bold; text-align: left; background-color: #fff;">অন্যান্য</td>
                                            <td style="border: 1px solid #000; padding: 2px 4px; text-align: center; font-weight: bold;" x-text="batchOtherFee ? toBanglaNum(batchOtherFee) : ''"></td>
                                        </tr>
                                        <tr style="background-color: #f3f4f6; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                                            <td style="border: 1px solid #000; padding: 2px 4px; font-weight: bold; text-align: left;">মোট =</td>
                                            <td style="border: 1px solid #000; padding: 2px 4px; text-align: center; font-weight: 900;" x-text="(batchTuitionFee || batchExamFee || batchOtherFee) ? toBanglaNum(Number(batchTuitionFee || 0) + Number(batchExamFee || 0) + Number(batchOtherFee || 0)) : ''"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Middle -->
                            <div class="col-span-4 text-center pb-1">
                                <p class="text-xs" style="margin: 0; letter-spacing: 1px;">............................</p>
                                <p class="font-bold text-[14px]" style="margin: 1px 0 0 0;">শ্রেণী শিক্ষকের স্বাক্ষর</p>
                            </div>
                            
                            <!-- Right -->
                            <div class="col-span-3 text-right pb-1 font-black text-sm" x-text="`প্রয়োজনে : ${toBanglaNum(printConfig.announcement.phone || '০১৮১৬-২২০৩০০')}`"></div>
                        </div>
                    </div>

                    <!-- FLYER BOTTOM HALF -->
                    <div class="flyer-half" style="height: 138mm !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; padding: 6mm 5mm 4mm 5mm; box-sizing: border-box; background-color: #fff !important;">
                        <div class="text-center" style="margin-bottom: 1.5mm;">
                            <p class="font-extrabold uppercase text-[16px]" style="margin: 0;">বিসমিল্লাহির রাহমানির রাহিম</p>
                            <h3 class="text-xl font-black" style="margin: 1px 0 0 0; color: #000;">ম্যাকস স্কুল এন্ড কলেজ</h3>
                            <p class="text-[16px] font-bold" style="margin: 1px 0 0 0;" x-text="`${printConfig.announcement.title} নোটিশ`"></p>
                        </div>
                        
                        <div class="font-bold" style="padding: 0 5px;">
                            <p style="margin: 0; font-size: 16px;">সম্মানিত অভিভাবক ও সুপ্রিয় শিক্ষার্থী,</p>
                            <p class="text-justify font-semibold" style="text-indent: 1.5em; margin: 2px 0 0 0; font-size: 16px;" x-text="printConfig.announcement.text"></p>
                        </div>

                        <!-- Right Signatures Area -->
                        <div class="flex justify-end" style="padding-right: 15px; margin-top: 4mm;">
                            <div class="text-center" style="line-height: 1.2;">
                                <p class="font-extrabold text-[16px]" style="margin: 0;" x-text="printConfig.announcement.principalName || 'মা-আসসালাম'"></p>
                                <div style="height: 5mm;"></div>
                                <p class="font-black text-[16px]" style="margin: 0;" x-text="printConfig.announcement.principalTitle || 'অধ্যক্ষ'"></p>
                                <p class="font-bold text-[16px]" style="margin: 0;">ম্যাকস স্কুল এন্ড কলেজ</p>
                            </div>
                        </div>

                        <!-- Student Info Blanks -->
                        <div class="font-bold text-sm" style="padding: 0 5px; margin-top: 4mm;">
                            <p style="margin: 0;">ছাত্র/ছাত্রীর নাম :.................................................................শ্রেণি :....................শাখা :..............রোল :....................</p>
                        </div>

                        <!-- Student Fee Box & Details (Directly Below Student Info) -->
                        <div class="grid grid-cols-12 gap-2 items-end" style="padding: 0 5px; margin-top: 2.5mm;">
                            <!-- Left table -->
                            <div class="col-span-5">
                                <table style="width: 100%; border-collapse: collapse; border: 1.5px solid #000; font-size: 10px; line-height: 0.8;">
                                    <tbody>
                                        <tr>
                                            <td style="border: 1px solid #000; padding: 2px 4px; font-weight: bold; width: 45%; text-align: left; background-color: #fff;">বেতন</td>
                                            <td style="border: 1px solid #000; padding: 2px 4px; width: 55%; text-align: center; font-weight: bold;" x-text="batchTuitionFee ? toBanglaNum(batchTuitionFee) : ''"></td>
                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid #000; padding: 2px 4px; font-weight: bold; text-align: left; background-color: #fff;">পরীক্ষা ফি</td>
                                            <td style="border: 1px solid #000; padding: 2px 4px; text-align: center; font-weight: bold;" x-text="batchExamFee ? toBanglaNum(batchExamFee) : ''"></td>
                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid #000; padding: 2px 4px; font-weight: bold; text-align: left; background-color: #fff;">অন্যান্য</td>
                                            <td style="border: 1px solid #000; padding: 2px 4px; text-align: center; font-weight: bold;" x-text="batchOtherFee ? toBanglaNum(batchOtherFee) : ''"></td>
                                        </tr>
                                        <tr style="background-color: #f3f4f6; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                                            <td style="border: 1px solid #000; padding: 2px 4px; font-weight: bold; text-align: left;">মোট =</td>
                                            <td style="border: 1px solid #000; padding: 2px 4px; text-align: center; font-weight: 900;" x-text="(batchTuitionFee || batchExamFee || batchOtherFee) ? toBanglaNum(Number(batchTuitionFee || 0) + Number(batchExamFee || 0) + Number(batchOtherFee || 0)) : ''"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Middle -->
                            <div class="col-span-4 text-center pb-1">
                                <p class="text-xs" style="margin: 0; letter-spacing: 1px;">............................</p>
                                <p class="font-bold text-[14px]" style="margin: 1px 0 0 0;">শ্রেণী শিক্ষকের স্বাক্ষর</p>
                            </div>
                            
                            <!-- Right -->
                            <div class="col-span-3 text-right pb-1 font-black text-sm" x-text="`প্রয়োজনে : ${toBanglaNum(printConfig.announcement.phone || '০১৮১৬-২২০৩০০')}`"></div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- BATCH STUDENT MODE (When a class is selected) -->
        <template x-if="batchClassId && batchStudents.length > 0">
            <div>
                <template x-for="(pair, pairIndex) in chunkedBatchStudents" :key="pairIndex">
                    <div>
                        <!-- PAGE 1: ROUTINES FRONT PAGE (ALWAYS OVERALL 2 SHIFTS) -->
                        <div class="print-page flex flex-col justify-between" style="height: 282mm !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; padding: 0; box-sizing: border-box; background-color: #fff !important;">
                            
                            <!-- SHIFT 1 TABLE (TOP HALF) -->
                            <div style="height: 133mm !important; display: flex !important; flex-direction: column !important; box-sizing: border-box; background-color: #fff !important;">
                                <!-- Header -->
                                <div class="text-center" style="margin-top: 1mm; margin-bottom: 1.5mm;">
                                    <h2 class="text-xl font-black" style="margin: 0 0 2px 0; color: #000; font-size: 22px;">ম্যাকস স্কুল এন্ড কলেজ</h2>
                                    <h3 class="text-xs font-extrabold" style="margin: 2px 0; font-size: 16px;" x-text="getPrintExamNameBengali()">২য় সাময়িক পরীক্ষা-২০২৬</h3>
                                    <div style="display: inline-block; border: 1.5px solid #000; border-radius: 3px; padding: 0px 10px; font-size: 18px; font-weight: 900; margin-top: 2px; background-color: #f3f4f6; -webkit-print-color-adjust: exact; print-color-adjust: exact;">রুটিন</div>
                                </div>
                                
                                <!-- Meta Info Row -->
                                <div class="flex justify-between items-center text-xs font-extrabold" style="margin-bottom: 1mm;">
        
                                    <!-- Left Side: Grouping First Two Divs -->
                                    <div class="flex items-center gap-3">
                                        <div style="border: 1.5px solid #000; border-radius: 3px; padding: 2px 6px; font-weight: 800; font-size: 14px;" x-text="printConfig.shift1.name">প্রথম শিফট</div>
                                        <div style="border: 1.5px solid #000; border-radius: 3px; padding: 2px 8px; font-weight: 800; font-size: 14px;" x-text="printConfig.shift1.classRange">প্লে - ৪র্থ</div>
                                    </div>

                                    <!-- Right Side: The Third Div -->
                                    <div style="border: 1.5px solid #000; border-radius: 3px; padding: 2px 6px; font-weight: 800; font-size: 14px;" x-text="printConfig.shift1.timeLabel">সময় : সকাল ৯.০০ থেকে ১১.০০ টা</div>

                                </div>
                                
                                <!-- Table -->
                                <table style="width: 100%; border-collapse: collapse; border: 1.5px solid #000; font-size: 9px; line-height: 0.3;">
                                    <thead>
                                        <tr class="routine-header-tr">
                                            <th class="routine-th" style="border: 1px solid #000; padding: 1px 0px; font-weight: bold; font-size: 10px; text-align: center; background-color: #d1d5db !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; width: 14%;">তারিখ</th>
                                            <th class="routine-th" style="border: 1px solid #000; padding: 1px 0px; font-weight: bold; font-size: 10px; text-align: center; background-color: #d1d5db !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; width: 14%;">বার</th>
                                            <template x-for="clsName in shift1ClassNames">
                                                <th class="routine-th" style="border: 1px solid #000; padding: 1px 0px; font-weight: bold; font-size: 10px; text-align: center; background-color: #d1d5db !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important;" x-text="translateClass(clsName)"></th>
                                            </template>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="row in shift1Rows">
                                            <tr>
                                                <td style="border: 1px solid #000; padding: 0.5px 0px; font-weight: bold; text-align: center; font-size: 9px;" x-text="formatDateBangla(row.date)"></td>
                                                <td style="border: 1px solid #000; padding: 0.5px 0px; font-weight: bold; text-align: center; font-size: 9px;" x-text="getDayBangla(row.date)"></td>
                                                <template x-for="clsId in printConfig.shift1.classes">
                                                    <td style="border: 1px solid #000; padding: 0.5px 0px; text-align: center; font-weight: bold;" :style="getSubjectStyle(row.subjects[clsId])" x-text="translateSubject(row.subjects[clsId])"></td>
                                                </template>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>

                                <!-- Spacer to push footnote to bottom -->
                                <div style="flex: 1;"></div>

                                <!-- Footnote -->
                                <div class="text-center text-[14px] font-bold" x-show="printConfig.shift1.footnote" x-text="printConfig.shift1.footnote" style="margin-top: 2mm; margin-bottom: 1mm;"></div>
                            </div>

                            <!-- SHIFT 2 TABLE (BOTTOM HALF) -->
                            <div style="height: 138mm !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; border-top: 1.5px dashed #000; padding-top: 4mm !important; box-sizing: border-box; background-color: #fff !important;">
                                <div>
                                    <!-- Header -->
                                    <div class="text-center" style="margin-top: 1mm; margin-bottom: 1.5mm;">
                                        <h2 class="text-xl font-black" style="margin: 0 0 2px 0; color: #000; font-size: 22px;">ম্যাকস স্কুল এন্ড কলেজ</h2>
                                        <h3 class="text-xs font-extrabold" style="margin: 2px 0; font-size: 16px;" x-text="getPrintExamNameBengali()">২য় সাময়িক পরীক্ষা-২০২৬</h3>
                                        <div style="display: inline-block; border: 1.5px solid #000; border-radius: 3px; padding: 0px 10px; font-size: 18px; font-weight: 900; margin-top: 2px; background-color: #f3f4f6; -webkit-print-color-adjust: exact; print-color-adjust: exact;">রুটিন</div>
                                    </div>
                                    
                                    <!-- Meta Info Row -->
                                    <div class="flex justify-between items-center text-xs font-extrabold" style="margin-bottom: 1mm;">
            
                                        <!-- Left Side: Grouping First Two Divs -->
                                        <div class="flex items-center gap-3">
                                            <div style="border: 1.5px solid #000; border-radius: 3px; padding: 2px 6px; font-weight: 800; font-size: 14px;" x-text="printConfig.shift2.name">দ্বিতীয় শিফট</div>
                                            <div style="border: 1.5px solid #000; border-radius: 3px; padding: 2px 8px; font-weight: 800; font-size: 14px;" x-text="printConfig.shift2.classRange">৫ম - ৯ম</div>
                                        </div>

                                        <!-- Right Side: The Third Div -->
                                        <div style="border: 1.5px solid #000; border-radius: 3px; padding: 2px 6px; font-weight: 800; font-size: 14px;" x-text="printConfig.shift2.timeLabel">সময় : দুপুর ১২.০০ থেকে ০২.০০ টা</div>

                                    </div>
                                    
                                    <!-- Table -->
                                    <table style="width: 100%; border-collapse: collapse; border: 1.5px solid #000; font-size: 9px; line-height: 0.3;">
                                        <thead>
                                            <tr class="routine-header-tr">
                                                <th class="routine-th" style="border: 1px solid #000; padding: 1px 0px; font-weight: bold; font-size: 10px; text-align: center; background-color: #d1d5db !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; width: 14%;">তারিখ</th>
                                                <th class="routine-th" style="border: 1px solid #000; padding: 1px 0px; font-weight: bold; font-size: 10px; text-align: center; background-color: #d1d5db !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; width: 14%;">বার</th>
                                                <template x-for="clsName in shift2ClassNames">
                                                    <th class="routine-th" style="border: 1px solid #000; padding: 1px 0px; font-weight: bold; font-size: 10px; text-align: center; background-color: #d1d5db !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important;" x-text="translateClass(clsName)"></th>
                                                </template>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="row in shift2Rows">
                                                <tr>
                                                    <td style="border: 1px solid #000; padding: 0.5px 0px; font-weight: bold; text-align: center; font-size: 9px;" x-text="formatDateBangla(row.date)"></td>
                                                    <td style="border: 1px solid #000; padding: 0.5px 0px; font-weight: bold; text-align: center; font-size: 9px;" x-text="getDayBangla(row.date)"></td>
                                                    <template x-for="clsId in printConfig.shift2.classes">
                                                        <td style="border: 1px solid #000; padding: 0.5px 0px; text-align: center; font-weight: bold;" :style="getSubjectStyle(row.subjects[clsId])" x-text="translateSubject(row.subjects[clsId])"></td>
                                                    </template>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>

                                    <!-- Footnote -->
                                    <div class="text-center text-[14px] font-bold" x-show="printConfig.shift2.footnote" x-text="printConfig.shift2.footnote" style="margin-top: 2mm;"></div>
                                </div>
                            </div>
                            
                        </div>
                        
                        <div class="page-break"></div>
                        
                        <!-- PAGE 2: PARENT FLYERS BACK PAGE (Top = Student A, Bottom = Student B) -->
                        <div class="print-page flex flex-col justify-between" style="height: 282mm !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; padding: 1mm 0; box-sizing: border-box; background-color: #fff !important;">
                            
                            <!-- TOP FLYER (Student A) -->
                            <div class="flyer-half" style="height: 138mm !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; border-bottom: 2px dashed #000; padding: 2mm 5mm 6mm 5mm; box-sizing: border-box; background-color: #fff !important;">
                                <div class="text-center" style="margin-bottom: 1.5mm;">
                                    <p class="font-extrabold uppercase text-[16px]" style="margin: 0;">বিসমিল্লাহির রাহমানির রাহিম</p>
                                    <h3 class="text-xl font-black" style="margin: 1px 0 0 0; color: #000;">ম্যাকস স্কুল এন্ড কলেজ</h3>
                                    <p class="text-[16px] font-bold" style="margin: 1px 0 0 0;" x-text="`${printConfig.announcement.title} নোটিশ`"></p>
                                </div>
                                
                                <div class="font-bold" style="padding: 0 5px;">
                                    <p style="margin: 0; font-size: 16px;">সম্মানিত অভিভাবক ও সুপ্রিয় শিক্ষার্থী,</p>
                                    <p class="text-justify font-semibold" style="text-indent: 1.5em; margin: 2px 0 0 0; font-size: 16px;" x-text="printConfig.announcement.text"></p>
                                </div>
                                
                                <!-- Right Signatures Area -->
                                <div class="flex justify-end" style="padding-right: 15px; margin-top: 4mm;">
                                    <div class="text-center" style="line-height: 1.2;">
                                        <p class="font-extrabold text-[16px]" style="margin: 0;" x-text="printConfig.announcement.principalName || 'মা-আসসালাম'"></p>
                                        <div style="height: 5mm;"></div>
                                        <p class="font-black text-[16px]" style="margin: 0;" x-text="printConfig.announcement.principalTitle || 'অধ্যক্ষ'"></p>
                                        <p class="font-bold text-[16px]" style="margin: 0;">ম্যাকস স্কুল এন্ড কলেজ</p>
                                    </div>
                                </div>
                                
                                <!-- Student Info Dynamic (Student A) -->
                                <div class="font-bold text-sm" style="padding: 0 5px; margin-top: 4mm;">
                                    <p style="margin: 0;">
                                        <span>ছাত্র/ছাত্রীর নাম : </span><span class="font-black underline" x-text="pair[0].student_name"></span>
                                        <span style="margin-left: 8px;">শ্রেণি : </span><span class="font-black underline" x-text="translateClass(batchClassName)"></span>
                                        <span style="margin-left: 8px;">শাখা : </span><span class="font-black underline" x-text="pair[0].section ? (pair[0].section.section_name.includes('A') ? 'এ' : (pair[0].section.section_name.includes('B') ? 'বি' : pair[0].section.section_name)) : ''"></span>
                                        <span style="margin-left: 8px;">রোল : </span><span class="font-black underline" x-text="toBanglaNum(pair[0].roll_number)"></span>
                                    </p>
                                </div>

                                <!-- Student Fee Box & Details (Directly Below Student Info) -->
                                <div class="grid grid-cols-12 gap-2 items-end" style="padding: 0 5px; margin-top: 2.5mm;">
                                    <!-- Left table -->
                                    <div class="col-span-5">
                                        <table style="width: 100%; border-collapse: collapse; border: 1.5px solid #000; font-size: 10px; line-height: 0.8;">
                                            <tbody>
                                                <tr>
                                                    <td style="border: 1px solid #000; padding: 2px 4px; font-weight: bold; width: 45%; text-align: left; background-color: #fff;">বেতন</td>
                                                    <td style="border: 1px solid #000; padding: 2px 4px; width: 55%; text-align: center; font-weight: bold;" x-text="batchTuitionFee ? toBanglaNum(batchTuitionFee) : ''"></td>
                                                </tr>
                                                <tr>
                                                    <td style="border: 1px solid #000; padding: 2px 4px; font-weight: bold; text-align: left; background-color: #fff;">পরীক্ষা ফি</td>
                                                    <td style="border: 1px solid #000; padding: 2px 4px; text-align: center; font-weight: bold;" x-text="batchExamFee ? toBanglaNum(batchExamFee) : ''"></td>
                                                </tr>
                                                <tr>
                                                    <td style="border: 1px solid #000; padding: 2px 4px; font-weight: bold; text-align: left; background-color: #fff;">অন্যান্য</td>
                                                    <td style="border: 1px solid #000; padding: 2px 4px; text-align: center; font-weight: bold;" x-text="batchOtherFee ? toBanglaNum(batchOtherFee) : ''"></td>
                                                </tr>
                                                <tr style="background-color: #f3f4f6; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                                                    <td style="border: 1px solid #000; padding: 2px 4px; font-weight: bold; text-align: left;">মোট =</td>
                                                    <td style="border: 1px solid #000; padding: 2px 4px; text-align: center; font-weight: 900;" x-text="(batchTuitionFee || batchExamFee || batchOtherFee) ? toBanglaNum(Number(batchTuitionFee || 0) + Number(batchExamFee || 0) + Number(batchOtherFee || 0)) : ''"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Middle -->
                                    <div class="col-span-4 text-center pb-1">
                                        <p class="text-xs" style="margin: 0; letter-spacing: 1px;">............................</p>
                                        <p class="font-bold text-[14px]" style="margin: 1px 0 0 0;">শ্রেণী শিক্ষকের স্বাক্ষর</p>
                                    </div>
                                    
                                    <!-- Right -->
                                    <div class="col-span-3 text-right pb-1 font-black text-sm" x-text="`প্রয়োজনে : ${toBanglaNum(printConfig.announcement.phone || '০১৮১৬-২২০৩০০')}`"></div>
                                </div>
                            </div>
                            
                            <!-- BOTTOM FLYER (Student B / Empty template if not exists) -->
                            <div class="flyer-half" style="height: 138mm !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; padding: 6mm 5mm 4mm 5mm; box-sizing: border-box; background-color: #fff !important;">
                                <div class="text-center" style="margin-bottom: 1.5mm;">
                                    <p class="font-extrabold uppercase text-[16px]" style="margin: 0;">বিসমিল্লাহির রাহমানির রাহিম</p>
                                    <h3 class="text-xl font-black" style="margin: 1px 0 0 0; color: #000;">ম্যাকস স্কুল এন্ড কলেজ</h3>
                                    <p class="text-[16px] font-bold" style="margin: 1px 0 0 0;" x-text="`${printConfig.announcement.title} নোটিশ`"></p>
                                </div>
                                
                                <div class="font-bold" style="padding: 0 5px;">
                                    <p style="margin: 0; font-size: 16px;">সম্মানিত অভিভাবক ও সুপ্রিয় শিক্ষার্থী,</p>
                                    <p class="text-justify font-semibold" style="text-indent: 1.5em; margin: 2px 0 0 0; font-size: 16px;" x-text="printConfig.announcement.text"></p>
                                </div>
                                
                                <!-- Right Signatures Area -->
                                <div class="flex justify-end" style="padding-right: 15px; margin-top: 4mm;">
                                    <div class="text-center" style="line-height: 1.2;">
                                        <p class="font-extrabold text-[16px]" style="margin: 0;" x-text="printConfig.announcement.principalName || 'মা-আসসালাম'"></p>
                                        <div style="height: 5mm;"></div>
                                        <p class="font-black text-[16px]" style="margin: 0;" x-text="printConfig.announcement.principalTitle || 'অধ্যক্ষ'"></p>
                                        <p class="font-bold text-[16px]" style="margin: 0;">ম্যাকস স্কুল এন্ড কলেজ</p>
                                    </div>
                                </div>
                                
                                <!-- Student Info Dynamic (Student B) -->
                                <div class="font-bold text-sm" style="padding: 0 5px; margin-top: 4mm;">
                                    <template x-if="pair[1]">
                                        <p style="margin: 0;">
                                            <span>ছাত্র/ছাত্রীর নাম : </span><span class="font-black underline" x-text="pair[1].student_name"></span>
                                            <span style="margin-left: 8px;">শ্রেণি : </span><span class="font-black underline" x-text="translateClass(batchClassName)"></span>
                                            <span style="margin-left: 8px;">শাখা : </span><span class="font-black underline" x-text="pair[1].section ? (pair[1].section.section_name.includes('A') ? 'এ' : (pair[1].section.section_name.includes('B') ? 'বি' : pair[1].section.section_name)) : ''"></span>
                                            <span style="margin-left: 8px;">রোল : </span><span class="font-black underline" x-text="toBanglaNum(pair[1].roll_number)"></span>
                                        </p>
                                    </template>
                                    <template x-if="!pair[1]">
                                        <p style="margin: 0;">ছাত্র/ছাত্রীর নাম :.................................................................শ্রেণি :....................শাখা :..............রো�                        </div>
                    </div>             </div>
                            </div>
                            
                        </div>(Directly Below Student Info) -->
                                <div class="grid grid-cols-12 gap-2 items-end" style="padding: 0 5px; margin-top: 2.5mm;">
                                    <!-- Left table -->
                                    <div class="col-span-5">
                                        <table style="width: 100%; border-collapse: collapse; border: 1.5px solid #000; font-size: 10px; line-height: 1.2;">
                                            <tbody>
                                                <tr>
                                                    <td style="border: 1px solid #000; padding: 2px 4px; font-weight: bold; width: 45%; text-align: left; background-color: #fff;">বেতন</td>
                                                    <td style="border: 1px solid #000; padding: 2px 4px; width: 55%; text-align: center; font-weight: bold;" x-text="pair[1] && batchTuitionFee ? toBanglaNum(batchTuitionFee) : '................'"></td>
                                                </tr>
                                                <tr>
                                                    <td style="border: 1px solid #000; padding: 2px 4px; font-weight: bold; text-align: left; background-color: #fff;">পরীক্ষা ফি</td>
                                                    <td style="border: 1px solid #000; padding: 2px 4px; text-align: center; font-weight: bold;" x-text="pair[1] && batchExamFee ? toBanglaNum(batchExamFee) : '................'"></td>
                                                </tr>
                                                <tr>
                                                    <td style="border: 1px solid #000; padding: 2px 4px; font-weight: bold; text-align: left; background-color: #fff;">অন্যান্য</td>
                                                    <td style="border: 1px solid #000; padding: 2px 4px; text-align: center; font-weight: bold;" x-text="pair[1] && batchOtherFee ? toBanglaNum(batchOtherFee) : '................'"></td>
                                                </tr>
                                                <tr style="background-color: #f3f4f6; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                                                    <td style="border: 1px solid #000; padding: 2px 4px; font-weight: bold; text-align: left;">মোট =</td>
                                                    <td style="border: 1px solid #000; padding: 2px 4px; text-align: center; font-weight: 900;" x-text="pair[1] && (batchTuitionFee || batchExamFee || batchOtherFee) ? toBanglaNum(Number(batchTuitionFee || 0) + Number(batchExamFee || 0) + Number(batchOtherFee || 0)) : '................'"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Middle -->
                                    <div class="col-span-4 text-center pb-1">
                                        <p class="text-xs" style="margin: 0; letter-spacing: 1px;">............................</p>
                                        <p class="font-bold text-[10px]" style="margin: 1px 0 0 0;">শ্রেণী শিক্ষকের স্বাক্ষর</p>
                                    </div>
                                    
                                    <!-- Right -->
                                    <div class="col-span-3 text-right pb-1 font-black text-xs" x-text="`প্রয়োজনে : ${toBanglaNum(printConfig.announcement.phone || '০১৮১৬-২২০৩০০')}`"></div>
                                </div>
                            </div>
                            
                        </div>
                        
                        <!-- Only add page break if it's not the last pair -->
                        <template x-if="pairIndex < chunkedBatchStudents.length - 1">
                            <div class="page-break"></div>
                        </template>
                    </div>
                </template>
            </div>
        </template>

    </div>
</div>

@endsection