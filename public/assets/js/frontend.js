// Mobile Header & Hamburger Navigation Enhancer
function initAdmMobileHeader() {
    const header = document.getElementById('site-header');
    if (!header || header.dataset.admEnhanced) return;
    header.dataset.admEnhanced = 'true';

    // Wrap header contents in .adm-header-container to align with max-w-7xl content
    let container = header.querySelector('.adm-header-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'adm-header-container';
        while (header.firstChild) {
            container.appendChild(header.firstChild);
        }
        header.appendChild(container);
        header.classList.add('has-adm-container');
    }

    // 1. Add Left Spacer (to keep logo centered on mobile)
    let spacer = container.querySelector('.adm-header-left-spacer');
    if (!spacer) {
        spacer = document.createElement('div');
        spacer.className = 'adm-header-left-spacer';
        container.insertBefore(spacer, container.firstChild);
    }

    // 2. Add Mobile Hamburger Toggle Button
    let toggleBtn = document.getElementById('adm-mobile-menu-toggle');
    if (!toggleBtn) {
        toggleBtn = document.createElement('button');
        toggleBtn.id = 'adm-mobile-menu-toggle';
        toggleBtn.type = 'button';
        toggleBtn.setAttribute('aria-label', 'Toggle Navigation');
        toggleBtn.setAttribute('aria-expanded', 'false');
        toggleBtn.innerHTML = `
            <svg class="adm-icon-bars" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="4" y1="6" x2="20" y2="6"></line>
                <line x1="4" y1="12" x2="20" y2="12"></line>
                <line x1="4" y1="18" x2="20" y2="18"></line>
            </svg>
            <svg class="adm-icon-close" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        `;
        container.appendChild(toggleBtn);
    }

    const iconBars = toggleBtn.querySelector('.adm-icon-bars');
    const iconClose = toggleBtn.querySelector('.adm-icon-close');

    function toggleMenu(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        const isOpen = header.classList.toggle('adm-menu-open');
        toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        if (iconBars && iconClose) {
            iconBars.style.display = isOpen ? 'none' : 'block';
            iconClose.style.display = isOpen ? 'block' : 'none';
        }
    }

    toggleBtn.addEventListener('click', toggleMenu);

    // Close when clicking outside header
    document.addEventListener('click', function(e) {
        if (header.classList.contains('adm-menu-open') && !header.contains(e.target)) {
            toggleMenu();
        }
    });

    // Close when clicking menu links
    const nav = header.querySelector('.site-navigation');
    if (nav) {
        nav.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function() {
                if (header.classList.contains('adm-menu-open')) {
                    toggleMenu();
                }
            });
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdmMobileHeader);
} else {
    initAdmMobileHeader();
}

// Alpine.js Component for Land Search
function admLandSearchComponent(config) {
    config = config || (typeof window !== 'undefined' ? window.admInitialLandConfig : null) || {};
    return {
        loading: false,
        showMobileFilter: false,
        properties: config.properties || [],
        total: config.total || 0,
        currentPage: config.page || 1,
        perPage: 12,
        totalPages: Math.max(1, Math.ceil((config.total || 0) / 12)),
        areasHierarchy: config.areasHierarchy || {},
        filterForm: {
            area: config.filterForm ? (config.filterForm.area || '') : '',
            sub_area: config.filterForm ? (config.filterForm.sub_area || '') : '',
            block: config.filterForm && config.filterForm.block ? config.filterForm.block : [],
            sector: '',
            road_number: '',
            road_width: '',
            facing: '',
            is_negotiable: '',
            min_katha: '',
            max_katha: '',
            min_price_per_katha: '',
            max_price_per_katha: '',
            min_price: '',
            max_price: '',
            corner: false,
            lake_facing: false,
            boundary_wall: false,
            soil_filling: '',
            source_type: '',
            sort: 'newest'
        },
        shortlist: (function() {
            try { return JSON.parse(localStorage.getItem('aftabnagar_shortlist') || '[]').map(Number); } catch(e) { return []; }
        })(),
        compare: (function() {
            try { return JSON.parse(localStorage.getItem('aftabnagar_compare') || '[]').map(Number); } catch(e) { return []; }
        })(),
        init() {
            window.addEventListener('shortlist-updated', () => {
                try { this.shortlist = JSON.parse(localStorage.getItem('aftabnagar_shortlist') || '[]').map(Number); } catch(e) {}
            });
            window.addEventListener('compare-updated', () => {
                try { this.compare = JSON.parse(localStorage.getItem('aftabnagar_compare') || '[]').map(Number); } catch(e) {}
            });
        },
        getCurrentAreaConfig() {
            return this.areasHierarchy[this.filterForm.area] || null;
        },
        onAreaChange() {
            this.filterForm.sub_area = '';
            this.filterForm.block = [];
            this.fetchLand(1);
        },
        goToPage(p) {
            if (p < 1 || p > this.totalPages || p === this.currentPage) return;
            this.fetchLand(p);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        get paginationPages() {
            const total = this.totalPages;
            const current = this.currentPage;
            if (total <= 7) {
                return Array.from({ length: total }, (_, i) => i + 1);
            }
            const pages = [];
            pages.push(1);
            if (current > 3) {
                pages.push('...');
            }
            const start = Math.max(2, current - 1);
            const end = Math.min(total - 1, current + 1);
            for (let i = start; i <= end; i++) {
                pages.push(i);
            }
            if (current < total - 2) {
                pages.push('...');
            }
            pages.push(total);
            return pages;
        },
        resetFilters() {
            this.filterForm = {
                area: '',
                sub_area: '',
                block: [],
                sector: '',
                road_number: '',
                road_width: '',
                facing: '',
                is_negotiable: '',
                min_katha: '',
                max_katha: '',
                min_price_per_katha: '',
                max_price_per_katha: '',
                min_price: '',
                max_price: '',
                corner: false,
                lake_facing: false,
                boundary_wall: false,
                soil_filling: '',
                source_type: '',
                sort: 'newest'
            };
            this.fetchLand(1);
        },
        isShortlisted(id) {
            return this.shortlist.includes(Number(id));
        },
        toggleShortlist(id) {
            const targetId = Number(id);
            let list = [];
            try { list = JSON.parse(localStorage.getItem('aftabnagar_shortlist') || '[]').map(Number); } catch(e) {}
            if (this.isShortlisted(targetId)) {
                list = list.filter(item => item !== targetId);
            } else {
                if (!list.includes(targetId)) list.push(targetId);
            }
            this.shortlist = list;
            try { localStorage.setItem('aftabnagar_shortlist', JSON.stringify(list)); } catch(e) {}
            window.dispatchEvent(new CustomEvent('shortlist-updated'));

            if (typeof admVars !== 'undefined' && admVars.ajaxUrl) {
                const fd = new FormData();
                fd.append('action', 'adm_toggle_favorite');
                fd.append('property_id', targetId);
                fd.append('fav_action', 'toggle');
                fetch(admVars.ajaxUrl, { method: 'POST', body: fd }).catch(() => {});
            }
        },
        isCompared(id) {
            return this.compare.includes(Number(id));
        },
        toggleCompare(id) {
            const targetId = Number(id);
            let comp = [];
            try { comp = JSON.parse(localStorage.getItem('aftabnagar_compare') || '[]').map(Number); } catch(e) {}
            if (this.isCompared(targetId)) {
                comp = comp.filter(item => item !== targetId);
            } else {
                if (comp.length >= 4) {
                    alert('সর্বোচ্চ ৪টি প্রপার্টি একসাথে তুলনা করা যাবে');
                    return;
                }
                if (!comp.includes(targetId)) comp.push(targetId);
            }
            this.compare = comp;
            try { localStorage.setItem('aftabnagar_compare', JSON.stringify(comp)); } catch(e) {}
            window.dispatchEvent(new CustomEvent('compare-updated'));
        },
        fetchLand(targetPage = 1) {
            this.loading = true;
            this.currentPage = targetPage;
            const formData = new FormData();
            formData.append('action', 'adm_filter_land');
            formData.append('page', this.currentPage);
            formData.append('limit', this.perPage);
            if (this.filterForm.area) formData.append('area', this.filterForm.area);
            if (this.filterForm.sub_area) formData.append('sub_area', this.filterForm.sub_area);
            if (this.filterForm.block && this.filterForm.block.length > 0) {
                this.filterForm.block.forEach(b => formData.append('block[]', b));
            }
            formData.append('sector', this.filterForm.sector);
            formData.append('road_number', this.filterForm.road_number);
            formData.append('road_width', this.filterForm.road_width);
            formData.append('facing', this.filterForm.facing);
            formData.append('is_negotiable', this.filterForm.is_negotiable);
            formData.append('min_katha', this.filterForm.min_katha);
            formData.append('max_katha', this.filterForm.max_katha);
            formData.append('min_price_per_katha', this.filterForm.min_price_per_katha);
            formData.append('max_price_per_katha', this.filterForm.max_price_per_katha);
            formData.append('min_price', this.filterForm.min_price);
            formData.append('max_price', this.filterForm.max_price);
            if (this.filterForm.corner) formData.append('corner', '1');
            if (this.filterForm.lake_facing) formData.append('lake_facing', '1');
            if (this.filterForm.boundary_wall) formData.append('boundary_wall', '1');
            formData.append('soil_filling', this.filterForm.soil_filling);
            formData.append('source_type', this.filterForm.source_type);
            formData.append('sort', this.filterForm.sort);

            const ajaxUrl = (typeof admVars !== 'undefined' && admVars.ajaxUrl) ? admVars.ajaxUrl : '/wp-admin/admin-ajax.php';
            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                this.loading = false;
                if (data.success) {
                    this.properties = data.data.properties;
                    this.total = data.data.total;
                    this.currentPage = data.data.page || 1;
                    this.totalPages = data.data.total_pages || 1;
                }
            })
            .catch(() => this.loading = false);
        }
    };
}

// Alpine.js Component for Flat Search
function admFlatSearchComponent(config) {
    config = config || (typeof window !== 'undefined' ? window.admInitialFlatConfig : null) || {};
    return {
        loading: false,
        showMobileFilter: false,
        properties: config.properties || [],
        total: config.total || 0,
        currentPage: config.page || 1,
        perPage: 12,
        totalPages: Math.max(1, Math.ceil((config.total || 0) / 12)),
        areasHierarchy: config.areasHierarchy || {},
        filterForm: {
            area: config.filterForm ? (config.filterForm.area || '') : '',
            sub_area: config.filterForm ? (config.filterForm.sub_area || '') : '',
            block: config.filterForm && config.filterForm.block ? config.filterForm.block : [],
            sector: '',
            road_number: '',
            road_width: '',
            completion_status: '',
            bedrooms: '',
            bathrooms: '',
            min_sqft: '',
            max_sqft: '',
            min_price: '',
            max_price: '',
            is_negotiable: '',
            parking: false,
            lift: false,
            generator: false,
            substation: false,
            gas: false,
            source_type: '',
            sort: 'newest'
        },
        shortlist: (function() {
            try { return JSON.parse(localStorage.getItem('aftabnagar_shortlist') || '[]').map(Number); } catch(e) { return []; }
        })(),
        compare: (function() {
            try { return JSON.parse(localStorage.getItem('aftabnagar_compare') || '[]').map(Number); } catch(e) { return []; }
        })(),
        init() {
            window.addEventListener('shortlist-updated', () => {
                try { this.shortlist = JSON.parse(localStorage.getItem('aftabnagar_shortlist') || '[]').map(Number); } catch(e) {}
            });
            window.addEventListener('compare-updated', () => {
                try { this.compare = JSON.parse(localStorage.getItem('aftabnagar_compare') || '[]').map(Number); } catch(e) {}
            });
        },
        getCurrentAreaConfig() {
            return this.areasHierarchy[this.filterForm.area] || null;
        },
        onAreaChange() {
            this.filterForm.sub_area = '';
            this.filterForm.block = [];
            this.fetchFlat(1);
        },
        goToPage(p) {
            if (p < 1 || p > this.totalPages || p === this.currentPage) return;
            this.fetchFlat(p);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        get paginationPages() {
            const total = this.totalPages;
            const current = this.currentPage;
            if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
            const pages = [];
            pages.push(1);
            if (current > 3) pages.push('...');
            const start = Math.max(2, current - 1);
            const end = Math.min(total - 1, current + 1);
            for (let i = start; i <= end; i++) pages.push(i);
            if (current < total - 2) pages.push('...');
            pages.push(total);
            return pages;
        },
        resetFilters() {
            this.filterForm = {
                area: '',
                sub_area: '',
                block: [],
                sector: '',
                road_number: '',
                road_width: '',
                completion_status: '',
                bedrooms: '',
                bathrooms: '',
                min_sqft: '',
                max_sqft: '',
                min_price: '',
                max_price: '',
                is_negotiable: '',
                parking: false,
                lift: false,
                generator: false,
                substation: false,
                gas: false,
                source_type: '',
                sort: 'newest'
            };
            this.fetchFlat(1);
        },
        isShortlisted(id) {
            return this.shortlist.includes(Number(id));
        },
        toggleShortlist(id) {
            const targetId = Number(id);
            let list = [];
            try { list = JSON.parse(localStorage.getItem('aftabnagar_shortlist') || '[]').map(Number); } catch(e) {}
            if (this.isShortlisted(targetId)) list = list.filter(item => item !== targetId);
            else if (!list.includes(targetId)) list.push(targetId);
            this.shortlist = list;
            try { localStorage.setItem('aftabnagar_shortlist', JSON.stringify(list)); } catch(e) {}
            window.dispatchEvent(new CustomEvent('shortlist-updated'));

            if (typeof admVars !== 'undefined' && admVars.ajaxUrl) {
                const fd = new FormData();
                fd.append('action', 'adm_toggle_favorite');
                fd.append('property_id', targetId);
                fd.append('fav_action', 'toggle');
                fetch(admVars.ajaxUrl, { method: 'POST', body: fd }).catch(() => {});
            }
        },
        isCompared(id) {
            return this.compare.includes(Number(id));
        },
        toggleCompare(id) {
            const targetId = Number(id);
            let comp = [];
            try { comp = JSON.parse(localStorage.getItem('aftabnagar_compare') || '[]').map(Number); } catch(e) {}
            if (this.isCompared(targetId)) comp = comp.filter(item => item !== targetId);
            else {
                if (comp.length >= 4) { alert('সর্বোচ্চ ৪টি প্রপার্টি একসাথে তুলনা করা যাবে'); return; }
                if (!comp.includes(targetId)) comp.push(targetId);
            }
            this.compare = comp;
            try { localStorage.setItem('aftabnagar_compare', JSON.stringify(comp)); } catch(e) {}
            window.dispatchEvent(new CustomEvent('compare-updated'));
        },
        fetchFlat(targetPage = 1) {
            this.loading = true;
            this.currentPage = targetPage;
            const formData = new FormData();
            formData.append('action', 'adm_filter_flats');
            formData.append('page', this.currentPage);
            formData.append('limit', this.perPage);
            if (this.filterForm.area) formData.append('area', this.filterForm.area);
            if (this.filterForm.sub_area) formData.append('sub_area', this.filterForm.sub_area);
            if (this.filterForm.block && this.filterForm.block.length > 0) {
                this.filterForm.block.forEach(b => formData.append('block[]', b));
            }
            formData.append('sector', this.filterForm.sector);
            formData.append('road_number', this.filterForm.road_number);
            formData.append('road_width', this.filterForm.road_width);
            formData.append('completion_status', this.filterForm.completion_status);
            formData.append('bedrooms', this.filterForm.bedrooms);
            formData.append('bathrooms', this.filterForm.bathrooms);
            formData.append('min_sqft', this.filterForm.min_sqft);
            formData.append('max_sqft', this.filterForm.max_sqft);
            formData.append('min_price', this.filterForm.min_price);
            formData.append('max_price', this.filterForm.max_price);
            formData.append('is_negotiable', this.filterForm.is_negotiable);
            if (this.filterForm.parking) formData.append('parking', '1');
            if (this.filterForm.lift) formData.append('lift', '1');
            if (this.filterForm.generator) formData.append('generator', '1');
            if (this.filterForm.substation) formData.append('substation', '1');
            if (this.filterForm.gas) formData.append('gas', '1');
            formData.append('source_type', this.filterForm.source_type);
            formData.append('sort', this.filterForm.sort);

            const ajaxUrl = (typeof admVars !== 'undefined' && admVars.ajaxUrl) ? admVars.ajaxUrl : '/wp-admin/admin-ajax.php';
            fetch(ajaxUrl, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                this.loading = false;
                if (data.success) {
                    this.properties = data.data.properties;
                    this.total = data.data.total;
                    this.currentPage = data.data.page || 1;
                    this.totalPages = data.data.total_pages || 1;
                }
            })
            .catch(() => this.loading = false);
        }
    };
}

// Alpine.js Component for Land Share Search
function admLandShareSearchComponent(config) {
    config = config || (typeof window !== 'undefined' ? window.admInitialShareConfig : null) || {};
    return {
        loading: false,
        showMobileFilter: false,
        properties: config.properties || [],
        total: config.total || 0,
        currentPage: config.page || 1,
        perPage: 12,
        totalPages: Math.max(1, Math.ceil((config.total || 0) / 12)),
        areasHierarchy: config.areasHierarchy || {},
        filterForm: {
            area: config.filterForm ? (config.filterForm.area || '') : '',
            sub_area: config.filterForm ? (config.filterForm.sub_area || '') : '',
            block: config.filterForm && config.filterForm.block ? config.filterForm.block : [],
            sector: '',
            road_number: '',
            road_width: '',
            bedrooms: '',
            bathrooms: '',
            min_sqft: '',
            max_sqft: '',
            min_katha: '',
            max_katha: '',
            min_price: '',
            max_price: '',
            is_negotiable: '',
            parking: false,
            lift: false,
            generator: false,
            substation: false,
            source_type: '',
            sort: 'newest'
        },
        shortlist: (function() {
            try { return JSON.parse(localStorage.getItem('aftabnagar_shortlist') || '[]').map(Number); } catch(e) { return []; }
        })(),
        compare: (function() {
            try { return JSON.parse(localStorage.getItem('aftabnagar_compare') || '[]').map(Number); } catch(e) { return []; }
        })(),
        init() {
            window.addEventListener('shortlist-updated', () => {
                try { this.shortlist = JSON.parse(localStorage.getItem('aftabnagar_shortlist') || '[]').map(Number); } catch(e) {}
            });
            window.addEventListener('compare-updated', () => {
                try { this.compare = JSON.parse(localStorage.getItem('aftabnagar_compare') || '[]').map(Number); } catch(e) {}
            });
        },
        getCurrentAreaConfig() {
            return this.areasHierarchy[this.filterForm.area] || null;
        },
        onAreaChange() {
            this.filterForm.sub_area = '';
            this.filterForm.block = [];
            this.fetchShare(1);
        },
        goToPage(p) {
            if (p < 1 || p > this.totalPages || p === this.currentPage) return;
            this.fetchShare(p);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        get paginationPages() {
            const total = this.totalPages;
            const current = this.currentPage;
            if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
            const pages = [];
            pages.push(1);
            if (current > 3) pages.push('...');
            const start = Math.max(2, current - 1);
            const end = Math.min(total - 1, current + 1);
            for (let i = start; i <= end; i++) pages.push(i);
            if (current < total - 2) pages.push('...');
            pages.push(total);
            return pages;
        },
        resetFilters() {
            this.filterForm = {
                area: '',
                sub_area: '',
                block: [],
                sector: '',
                road_number: '',
                road_width: '',
                bedrooms: '',
                bathrooms: '',
                min_sqft: '',
                max_sqft: '',
                min_katha: '',
                max_katha: '',
                min_price: '',
                max_price: '',
                is_negotiable: '',
                parking: false,
                lift: false,
                generator: false,
                substation: false,
                source_type: '',
                sort: 'newest'
            };
            this.fetchShare(1);
        },
        isShortlisted(id) {
            return this.shortlist.includes(Number(id));
        },
        toggleShortlist(id) {
            const targetId = Number(id);
            let list = [];
            try { list = JSON.parse(localStorage.getItem('aftabnagar_shortlist') || '[]').map(Number); } catch(e) {}
            if (this.isShortlisted(targetId)) list = list.filter(item => item !== targetId);
            else if (!list.includes(targetId)) list.push(targetId);
            this.shortlist = list;
            try { localStorage.setItem('aftabnagar_shortlist', JSON.stringify(list)); } catch(e) {}
            window.dispatchEvent(new CustomEvent('shortlist-updated'));

            if (typeof admVars !== 'undefined' && admVars.ajaxUrl) {
                const fd = new FormData();
                fd.append('action', 'adm_toggle_favorite');
                fd.append('property_id', targetId);
                fd.append('fav_action', 'toggle');
                fetch(admVars.ajaxUrl, { method: 'POST', body: fd }).catch(() => {});
            }
        },
        isCompared(id) {
            return this.compare.includes(Number(id));
        },
        toggleCompare(id) {
            const targetId = Number(id);
            let comp = [];
            try { comp = JSON.parse(localStorage.getItem('aftabnagar_compare') || '[]').map(Number); } catch(e) {}
            if (this.isCompared(targetId)) comp = comp.filter(item => item !== targetId);
            else {
                if (comp.length >= 4) { alert('সর্বোচ্চ ৪টি প্রপার্টি একসাথে তুলনা করা যাবে'); return; }
                if (!comp.includes(targetId)) comp.push(targetId);
            }
            this.compare = comp;
            try { localStorage.setItem('aftabnagar_compare', JSON.stringify(comp)); } catch(e) {}
            window.dispatchEvent(new CustomEvent('compare-updated'));
        },
        fetchShare(targetPage = 1) {
            this.loading = true;
            this.currentPage = targetPage;
            const formData = new FormData();
            formData.append('action', 'adm_filter_land_share');
            formData.append('page', this.currentPage);
            formData.append('limit', this.perPage);
            if (this.filterForm.area) formData.append('area', this.filterForm.area);
            if (this.filterForm.sub_area) formData.append('sub_area', this.filterForm.sub_area);
            if (this.filterForm.block && this.filterForm.block.length > 0) {
                this.filterForm.block.forEach(b => formData.append('block[]', b));
            }
            formData.append('sector', this.filterForm.sector);
            formData.append('road_number', this.filterForm.road_number);
            formData.append('road_width', this.filterForm.road_width);
            formData.append('bedrooms', this.filterForm.bedrooms);
            formData.append('bathrooms', this.filterForm.bathrooms);
            formData.append('min_sqft', this.filterForm.min_sqft);
            formData.append('max_sqft', this.filterForm.max_sqft);
            formData.append('min_katha', this.filterForm.min_katha);
            formData.append('max_katha', this.filterForm.max_katha);
            formData.append('min_price', this.filterForm.min_price);
            formData.append('max_price', this.filterForm.max_price);
            formData.append('is_negotiable', this.filterForm.is_negotiable);
            if (this.filterForm.parking) formData.append('parking', '1');
            if (this.filterForm.lift) formData.append('lift', '1');
            if (this.filterForm.generator) formData.append('generator', '1');
            if (this.filterForm.substation) formData.append('substation', '1');
            formData.append('source_type', this.filterForm.source_type);
            formData.append('sort', this.filterForm.sort);

            const ajaxUrl = (typeof admVars !== 'undefined' && admVars.ajaxUrl) ? admVars.ajaxUrl : '/wp-admin/admin-ajax.php';
            fetch(ajaxUrl, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                this.loading = false;
                if (data.success) {
                    this.properties = data.data.properties;
                    this.total = data.data.total;
                    this.currentPage = data.data.page || 1;
                    this.totalPages = data.data.total_pages || 1;
                }
            })
            .catch(() => this.loading = false);
        }
    };
}

// Alpine.js Component for Commercial & Shop Search
function admCommercialSearchComponent(config) {
    config = config || (typeof window !== 'undefined' ? window.admInitialCommercialConfig : null) || {};
    return {
        loading: false,
        showMobileFilter: false,
        properties: config.properties || [],
        total: config.total || 0,
        currentPage: config.page || 1,
        perPage: 12,
        totalPages: Math.max(1, Math.ceil((config.total || 0) / 12)),
        areasHierarchy: config.areasHierarchy || {},
        commercialTypes: config.commercialTypes || {},
        filterForm: {
            area: config.filterForm ? (config.filterForm.area || '') : '',
            sub_area: config.filterForm ? (config.filterForm.sub_area || '') : '',
            block: config.filterForm && config.filterForm.block ? config.filterForm.block : [],
            commercial_type: config.filterForm ? (config.filterForm.commercial_type || '') : '',
            floor_number: '',
            market_name: '',
            completion_status: '',
            min_sqft: '',
            max_sqft: '',
            min_price: '',
            max_price: '',
            is_negotiable: '',
            parking: false,
            lift: false,
            escalator: false,
            generator: false,
            central_ac: false,
            cctv: false,
            fire_safety: false,
            source_type: '',
            sort: 'newest'
        },
        shortlist: (function() {
            try { return JSON.parse(localStorage.getItem('aftabnagar_shortlist') || '[]').map(Number); } catch(e) { return []; }
        })(),
        compare: (function() {
            try { return JSON.parse(localStorage.getItem('aftabnagar_compare') || '[]').map(Number); } catch(e) { return []; }
        })(),
        init() {
            window.addEventListener('shortlist-updated', () => {
                try { this.shortlist = JSON.parse(localStorage.getItem('aftabnagar_shortlist') || '[]').map(Number); } catch(e) {}
            });
            window.addEventListener('compare-updated', () => {
                try { this.compare = JSON.parse(localStorage.getItem('aftabnagar_compare') || '[]').map(Number); } catch(e) {}
            });
        },
        getCurrentAreaConfig() {
            return this.areasHierarchy[this.filterForm.area] || null;
        },
        onAreaChange() {
            this.filterForm.sub_area = '';
            this.filterForm.block = [];
            this.fetchCommercial(1);
        },
        goToPage(p) {
            if (p < 1 || p > this.totalPages || p === this.currentPage) return;
            this.fetchCommercial(p);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        get paginationPages() {
            const total = this.totalPages;
            const current = this.currentPage;
            if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
            const pages = [];
            pages.push(1);
            if (current > 3) pages.push('...');
            const start = Math.max(2, current - 1);
            const end = Math.min(total - 1, current + 1);
            for (let i = start; i <= end; i++) pages.push(i);
            if (current < total - 2) pages.push('...');
            pages.push(total);
            return pages;
        },
        resetFilters() {
            this.filterForm = {
                area: '',
                sub_area: '',
                block: [],
                commercial_type: '',
                floor_number: '',
                market_name: '',
                completion_status: '',
                min_sqft: '',
                max_sqft: '',
                min_price: '',
                max_price: '',
                is_negotiable: '',
                parking: false,
                lift: false,
                escalator: false,
                generator: false,
                central_ac: false,
                cctv: false,
                fire_safety: false,
                source_type: '',
                sort: 'newest'
            };
            this.fetchCommercial(1);
        },
        isShortlisted(id) {
            return this.shortlist.includes(Number(id));
        },
        toggleShortlist(id) {
            const targetId = Number(id);
            let list = [];
            try { list = JSON.parse(localStorage.getItem('aftabnagar_shortlist') || '[]').map(Number); } catch(e) {}
            if (this.isShortlisted(targetId)) list = list.filter(item => item !== targetId);
            else if (!list.includes(targetId)) list.push(targetId);
            this.shortlist = list;
            try { localStorage.setItem('aftabnagar_shortlist', JSON.stringify(list)); } catch(e) {}
            window.dispatchEvent(new CustomEvent('shortlist-updated'));

            if (typeof admVars !== 'undefined' && admVars.ajaxUrl) {
                const fd = new FormData();
                fd.append('action', 'adm_toggle_favorite');
                fd.append('property_id', targetId);
                fd.append('fav_action', 'toggle');
                fetch(admVars.ajaxUrl, { method: 'POST', body: fd }).catch(() => {});
            }
        },
        isCompared(id) {
            return this.compare.includes(Number(id));
        },
        toggleCompare(id) {
            const targetId = Number(id);
            let comp = [];
            try { comp = JSON.parse(localStorage.getItem('aftabnagar_compare') || '[]').map(Number); } catch(e) {}
            if (this.isCompared(targetId)) comp = comp.filter(item => item !== targetId);
            else {
                if (comp.length >= 4) { alert('সর্বোচ্চ ৪টি প্রপার্টি একসাথে তুলনা করা যাবে'); return; }
                if (!comp.includes(targetId)) comp.push(targetId);
            }
            this.compare = comp;
            try { localStorage.setItem('aftabnagar_compare', JSON.stringify(comp)); } catch(e) {}
            window.dispatchEvent(new CustomEvent('compare-updated'));
        },
        fetchCommercial(targetPage = 1) {
            this.loading = true;
            this.currentPage = targetPage;
            const formData = new FormData();
            formData.append('action', 'adm_filter_commercial');
            formData.append('page', this.currentPage);
            formData.append('limit', this.perPage);
            if (this.filterForm.area) formData.append('area', this.filterForm.area);
            if (this.filterForm.sub_area) formData.append('sub_area', this.filterForm.sub_area);
            if (this.filterForm.block && this.filterForm.block.length > 0) {
                this.filterForm.block.forEach(b => formData.append('block[]', b));
            }
            formData.append('commercial_type', this.filterForm.commercial_type);
            formData.append('floor_number', this.filterForm.floor_number);
            formData.append('market_name', this.filterForm.market_name);
            formData.append('completion_status', this.filterForm.completion_status);
            formData.append('min_sqft', this.filterForm.min_sqft);
            formData.append('max_sqft', this.filterForm.max_sqft);
            formData.append('min_price', this.filterForm.min_price);
            formData.append('max_price', this.filterForm.max_price);
            formData.append('is_negotiable', this.filterForm.is_negotiable);
            if (this.filterForm.parking) formData.append('parking', '1');
            if (this.filterForm.lift) formData.append('lift', '1');
            if (this.filterForm.escalator) formData.append('escalator', '1');
            if (this.filterForm.generator) formData.append('generator', '1');
            if (this.filterForm.central_ac) formData.append('central_ac', '1');
            if (this.filterForm.cctv) formData.append('cctv', '1');
            if (this.filterForm.fire_safety) formData.append('fire_safety', '1');
            formData.append('source_type', this.filterForm.source_type);
            formData.append('sort', this.filterForm.sort);

            const ajaxUrl = (typeof admVars !== 'undefined' && admVars.ajaxUrl) ? admVars.ajaxUrl : '/wp-admin/admin-ajax.php';
            fetch(ajaxUrl, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                this.loading = false;
                if (data.success) {
                    this.properties = data.data.properties;
                    this.total = data.data.total;
                    this.currentPage = data.data.page || 1;
                    this.totalPages = data.data.total_pages || 1;
                }
            })
            .catch(() => this.loading = false);
        }
    };
}

// Explicitly register on window and Alpine
if (typeof window !== 'undefined') {
    window.admLandSearchComponent = admLandSearchComponent;
    window.admFlatSearchComponent = admFlatSearchComponent;
    window.admLandShareSearchComponent = admLandShareSearchComponent;
    window.admCommercialSearchComponent = admCommercialSearchComponent;

    function registerAdmAlpineData() {
        if (typeof Alpine !== 'undefined' && typeof Alpine.data === 'function') {
            Alpine.data('admLandSearchComponent', function(config) { return admLandSearchComponent(config); });
            Alpine.data('admFlatSearchComponent', function(config) { return admFlatSearchComponent(config); });
            Alpine.data('admLandShareSearchComponent', function(config) { return admLandShareSearchComponent(config); });
            Alpine.data('admCommercialSearchComponent', function(config) { return admCommercialSearchComponent(config); });
        }
    }

    if (typeof Alpine !== 'undefined' && typeof Alpine.data === 'function') {
        registerAdmAlpineData();
    } else {
        document.addEventListener('alpine:init', registerAdmAlpineData);
    }
}

