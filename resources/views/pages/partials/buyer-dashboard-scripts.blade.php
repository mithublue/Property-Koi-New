<script>
window.admBuyerDashboard = function() {
    return {
            activeTab: 'favorites',
            categoryFilter: 'all',
            interactionFilter: 'all',
            searchKeyword: '',

            // Server-provided data
            favoritesList: @json($favorites),
            interactionsList: @json($interactions),
            myPropertiesList: @json($myProperties),
            demandsList: @json($demands),
            maxDemandsLimit: {{ $max_demands_limit ?? 3 }},

            demandModalOpen: false,
            savingDemand: false,
            runningCron: false,
            demandForm: {
                property_type: 'land',
                preferred_areas: ['Aftabnagar'],
                min_budget: '',
                max_budget: '',
                min_size: '',
                max_size: '',
                notify_channels: ['push', 'email', 'phone']
            },

            // My properties search & filters
            mySearchQuery: '',
            myTypeFilter: 'all',
            myStatusFilter: 'all',
            myBlockFilter: 'all',

            // Edit Modal State
            editModalOpen: false,
            editTab: 'basic',
            loadingProperty: false,
            savingProperty: false,
            uploadingMedia: false,
            editMsg: '',
            editSuccess: false,

            editForm: {
                id: null,
                property_code: '',
                type: 'land',
                title: '',
                description: '',
                fb_caption: '',
                post_to_facebook: false,
                price: '',
                block: 'A',
                sector: '',
                road_number: '',
                road_width_ft: 20,
                plot_number: '',
                holding_number: '',
                address_visibility: {
                    block: true,
                    sector: true,
                    road_number: true,
                    road_width_ft: true,
                    plot_number: true,
                    holding_number: true
                },
                facing: 'south',
                google_map_pin_url: '',
                latitude: '',
                longitude: '',
                is_negotiable: 1,
                owner_name: '',
                owner_phone: '',
                other_features: [],
                katha_size: '',
                land_type: 'residential',
                boundary_wall: 0,
                soil_filling: 'ready',
                corner_plot: 0,
                lake_facing: 0,
                front_road_width_ft: 20,
                flat_size_sqft: '',
                floor_number: 1,
                total_floors: 6,
                bedrooms: 3,
                bathrooms: 3,
                balconies: 2,
                has_parking: 1,
                handover_status: 'ready',
                handover_year: new Date().getFullYear(),
                down_payment: '',
                youtube_video_url: '',
                // Land Share specs
                project_name: '',
                developer_name: '',
                land_size_katha: '',
                building_stories: '',
                units_per_floor: '',
                total_shares: '',
                available_shares: '',
                has_drawing_dining: 0,
                has_family_living: 0,
                has_lift: 0,
                has_generator: 0,
                has_substation: 0,
                construction_start_date: '',
                construction_duration: '',
                current_progress: '',
                location_highlights: '',
                // Commercial specs
                commercial_type: 'shop',
                market_name: '',
                shop_number: '',
                size_sqft: '',
                price_per_sqft: '',
                frontage_ft: '',
                ceiling_height_ft: '',
                monthly_rent_yield: '',
                washroom_type: 'common',
                has_escalator: 0,
                has_central_ac: 0,
                has_cctv: 0,
                has_fire_safety: 0,
                is_corner: 0,
                is_main_road_facing: 0
            },
            mediaList: [],

            // Profile
            profileForm: {
                displayName: @json($profile['display_name']),
                email: @json($profile['email']),
                isEmailVerified: @json($profile['is_email_verified']),
                phone: @json($profile['phone']),
                whatsappNumber: @json($profile['whatsapp_number']),
                preferredType: @json($profile['preferred_type']),
                preferredBlocks: @json($profile['preferred_blocks']),
                budgetRange: @json($profile['budget_range']),
                avatarUrl: @json($profile['avatar_url']),
                avatarFile: null,
                avatarPreview: null,
                password: ''
            },
            copyWhatsappToAlt: @json(!empty($profile['whatsapp_number']) && !empty($profile['phone']) && $profile['whatsapp_number'] === $profile['phone']),
            savingProfile: false,
            profileMsg: '',
            profileSuccess: false,

            // Email verification code box
            verifyCodeInput: '',
            sendingVerifyEmail: false,
            verifyingCode: false,
            verifyNotice: '',
            verifyNoticeSuccess: false,
            codeSent: false,

            initDashboard() {
                var self = this;

                // 2-Way Favorites Sync with localStorage
                try {
                    var serverFavIds = (self.favoritesList || []).map(function(p) { return Number(p.id); }).filter(Boolean);
                    var rawA = localStorage.getItem('aftabnagar_shortlist');
                    var rawB = localStorage.getItem('propertykoi_shortlist');
                    var listA = rawA ? JSON.parse(rawA) : [];
                    var listB = rawB ? JSON.parse(rawB) : [];
                    var combined = [
                        ...(Array.isArray(listA) ? listA : []),
                        ...(Array.isArray(listB) ? listB : [])
                    ];
                    var localIds = Array.from(new Set(combined.map(Number).filter(Boolean)));

                    // Push any guest localStorage favorites to server
                    var missingOnServer = localIds.filter(function(id) { return !serverFavIds.includes(id); });
                    if (missingOnServer.length > 0) {
                        missingOnServer.forEach(function(propId) {
                            fetch("{{ route('buyer.dashboard.fav.toggle') }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({ property_id: propId })
                            }).catch(function() {});
                        });
                    }

                    // Save unified list to both localStorage keys
                    var merged = Array.from(new Set([...localIds, ...serverFavIds]));
                    localStorage.setItem('aftabnagar_shortlist', JSON.stringify(merged));
                    localStorage.setItem('propertykoi_shortlist', JSON.stringify(merged));
                } catch(e) {}

                if (this.$watch) {
                    this.$watch('profileForm.whatsappNumber', function(val) {
                        if (self.copyWhatsappToAlt) {
                            self.profileForm.phone = val;
                        }
                    });
                    this.$watch('profileForm.email', function(newVal, oldVal) {
                        if (newVal !== oldVal) {
                            self.codeSent = false;
                            self.verifyNotice = '';
                        }
                    });
                }
            },

            filteredFavorites() {
                var self = this;
                return self.favoritesList.filter(function(p) {
                    var matchCat = (self.categoryFilter === 'all') || (p.type === self.categoryFilter);
                    var matchSearch = true;
                    if (self.searchKeyword && self.searchKeyword.trim() !== '') {
                        var kw = self.searchKeyword.toLowerCase().trim();
                        matchSearch = (p.title && p.title.toLowerCase().includes(kw)) ||
                                      (p.block && p.block.toLowerCase().includes(kw)) ||
                                      (p.property_code && p.property_code.toLowerCase().includes(kw));
                    }
                    return matchCat && matchSearch;
                });
            },

            filteredInteractions() {
                var self = this;
                if (self.interactionFilter === 'all') {
                    return self.interactionsList;
                }
                return self.interactionsList.filter(function(i) {
                    return i.interaction_type === self.interactionFilter;
                });
            },

            countInteractions(type) {
                return this.interactionsList.filter(function(i) {
                    return i.interaction_type === type;
                }).length;
            },

            filteredMyProperties() {
                var self = this;
                return (self.myPropertiesList || []).filter(function(p) {
                    if (self.mySearchQuery && self.mySearchQuery.trim() !== '') {
                        var q = self.mySearchQuery.toLowerCase().trim();
                        var codeMatch = (p.property_code || '').toLowerCase().includes(q);
                        var titleMatch = (p.title || '').toLowerCase().includes(q);
                        var plotMatch = (p.plot_number || '').toLowerCase().includes(q);
                        if (!codeMatch && !titleMatch && !plotMatch) return false;
                    }
                    if (self.myTypeFilter !== 'all' && p.type !== self.myTypeFilter) return false;
                    if (self.myStatusFilter !== 'all' && p.status !== self.myStatusFilter) return false;
                    if (self.myBlockFilter !== 'all' && p.block !== self.myBlockFilter) return false;
                    return true;
                });
            },

            totalDemandMatches() {
                var sum = 0;
                if (Array.isArray(this.demandsList)) {
                    this.demandsList.forEach(function(d) {
                        sum += (d.matches ? d.matches.length : 0);
                    });
                }
                return sum;
            },

            removeFavorite(propId) {
                var self = this;
                var targetId = Number(propId);

                // Update localStorage
                try {
                    var rawA = localStorage.getItem('aftabnagar_shortlist');
                    var rawB = localStorage.getItem('propertykoi_shortlist');
                    var listA = rawA ? JSON.parse(rawA) : [];
                    var listB = rawB ? JSON.parse(rawB) : [];
                    var combined = [
                        ...(Array.isArray(listA) ? listA : []),
                        ...(Array.isArray(listB) ? listB : [])
                    ];
                    var updated = Array.from(new Set(combined.map(Number).filter(Boolean))).filter(function(x) {
                        return x !== targetId;
                    });
                    localStorage.setItem('aftabnagar_shortlist', JSON.stringify(updated));
                    localStorage.setItem('propertykoi_shortlist', JSON.stringify(updated));
                } catch(e) {}
                window.dispatchEvent(new CustomEvent('shortlist-updated'));

                fetch("{{ route('buyer.dashboard.fav.remove') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ property_id: targetId })
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res && res.success) {
                        self.favoritesList = self.favoritesList.filter(function(p) { return Number(p.id) !== targetId; });
                    }
                });
            },

            handleAvatarChange(event) {
                var file = event.target.files[0];
                if (file) {
                    this.profileForm.avatarFile = file;
                    this.profileForm.avatarPreview = URL.createObjectURL(file);
                }
            },

            saveProfile() {
                var self = this;
                self.savingProfile = true;
                self.profileMsg = '';

                var fd = new FormData();
                fd.append('displayName', self.profileForm.displayName);
                fd.append('email', self.profileForm.email);
                fd.append('whatsappNumber', self.profileForm.whatsappNumber || '');
                fd.append('phone', self.profileForm.phone || '');
                fd.append('preferredType', self.profileForm.preferredType || 'any');
                fd.append('preferredBlocks', self.profileForm.preferredBlocks || '');
                fd.append('budgetRange', self.profileForm.budgetRange || '');
                if (self.profileForm.password) {
                    fd.append('password', self.profileForm.password);
                }
                if (self.profileForm.avatarFile) {
                    fd.append('avatar', self.profileForm.avatarFile);
                }

                fetch("{{ route('buyer.dashboard.profile') }}", {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: fd
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    self.savingProfile = false;
                    self.profileMsg = res.message;
                    self.profileSuccess = res.success;
                    if (res.success) {
                        self.profileForm.isEmailVerified = res.is_email_verified;
                        if (res.avatar_url) {
                            self.profileForm.avatarUrl = res.avatar_url;
                            self.profileForm.avatarPreview = null;
                            self.profileForm.avatarFile = null;
                        }
                        self.profileForm.password = '';
                    }
                })
                .catch(function() {
                    self.savingProfile = false;
                    self.profileMsg = 'সার্ভারে সমস্যা হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।';
                    self.profileSuccess = false;
                });
            },

            sendVerificationEmail() {
                var self = this;
                self.sendingVerifyEmail = true;
                self.verifyNotice = '';

                fetch("{{ route('auth.resend-verification') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    self.sendingVerifyEmail = false;
                    self.codeSent = true;
                    self.verifyNoticeSuccess = res.success;
                    self.verifyNotice = res.message;
                })
                .catch(function() {
                    self.sendingVerifyEmail = false;
                    self.verifyNoticeSuccess = false;
                    self.verifyNotice = 'ইমেইল পাঠাতে সমস্যা হয়েছে।';
                });
            },

            verifyEmailCode() {
                var self = this;
                if (!self.verifyCodeInput) return;
                self.verifyingCode = true;
                self.verifyNotice = '';

                fetch("{{ route('auth.verify-code') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ code: self.verifyCodeInput })
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    self.verifyingCode = false;
                    if (res.success) {
                        self.verifyNoticeSuccess = true;
                        self.verifyNotice = res.message;
                        self.profileForm.isEmailVerified = true;
                        self.verifyCodeInput = '';
                    } else {
                        self.verifyNoticeSuccess = false;
                        self.verifyNotice = res.message || 'ভুল ওটিপি কোড!';
                    }
                })
                .catch(function() {
                    self.verifyingCode = false;
                    self.verifyNoticeSuccess = false;
                    self.verifyNotice = 'সার্ভার সমস্যা, পুনরায় চেষ্টা করুন।';
                });
            },

            toggleModalArea(area) {
                var idx = this.demandForm.preferred_areas.indexOf(area);
                if (idx > -1) {
                    if (this.demandForm.preferred_areas.length > 1) {
                        this.demandForm.preferred_areas.splice(idx, 1);
                    }
                } else {
                    this.demandForm.preferred_areas.push(area);
                }
            },

            submitDemandModal() {
                var self = this;
                var activeDemandsCount = (self.demandsList || []).filter(function(d) { return d.status === 'active'; }).length;
                if (self.maxDemandsLimit !== 999 && activeDemandsCount >= self.maxDemandsLimit) {
                    alert('আপনি ইতিমধ্যে সর্বোচ্চ ' + self.maxDemandsLimit + 'টি সক্রিয় চাহিদা সাবমিট করেছেন। নতুন চাহিদা জমা দিতে হলে তালিকা থেকে যেকোনো একটি পুরনো চাহিদা মুছে ফেলুন বা বন্ধ করুন।');
                    return;
                }
                self.savingDemand = true;

                fetch("{{ route('buyer.dashboard.demand.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(self.demandForm)
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    self.savingDemand = false;
                    if (res.success) {
                        alert(res.message);
                        self.demandModalOpen = false;
                        window.location.reload();
                    } else {
                        alert(res.message || 'ত্রুটি ঘটেছে');
                    }
                })
                .catch(function() {
                    self.savingDemand = false;
                    alert('সার্ভারে যোগাযোগ করা যায়নি।');
                });
            },

            toggleDemandStatus(demandId, currentStatus) {
                var self = this;
                var nextStatus = currentStatus === 'active' ? 'closed' : 'active';

                fetch("{{ route('buyer.dashboard.demand.toggle') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ demand_id: demandId, status: nextStatus })
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        var item = self.demandsList.find(function(d) { return d.id === demandId; });
                        if (item) item.status = nextStatus;
                    }
                });
            },

            deleteDemand(demandId) {
                var self = this;
                if (!confirm('আপনি কি নিশ্চিতভাবে এই চাহিদাটি মুছে ফেলতে চান?')) return;

                fetch("{{ route('buyer.dashboard.demand.delete') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ demand_id: demandId })
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        self.demandsList = self.demandsList.filter(function(d) { return d.id !== demandId; });
                    }
                });
            },

            runDemandMatchCronManual() {
                var self = this;
                self.runningCron = true;

                fetch("{{ route('buyer.dashboard.demand.cron') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    self.runningCron = false;
                    alert(res.message);
                    if (res.success) window.location.reload();
                })
                .catch(function() {
                    self.runningCron = false;
                    alert('সার্ভারে সমস্যা হয়েছে।');
                });
            },

            addOtherFeature() {
                if (!this.editForm.other_features) this.editForm.other_features = [];
                this.editForm.other_features.push({ label: '', checked: true });
            },
            removeOtherFeature(idx) {
                this.editForm.other_features.splice(idx, 1);
            },

            openEditModal(propId) {
                var self = this;
                self.editModalOpen = true;
                self.loadingProperty = true;
                self.editTab = 'basic';
                self.editMsg = '';
                self.mediaList = [];

                // Fast instant preview from local table row
                var localProp = (self.myPropertiesList || []).find(function(item) { return item.id === propId; });
                if (localProp) {
                    self.editForm.id = localProp.id;
                    self.editForm.property_code = localProp.property_code || '';
                    self.editForm.type = localProp.type || 'land';
                    self.editForm.title = localProp.title || '';
                    self.editForm.price = localProp.price || '';
                    self.editForm.block = localProp.block || 'A';
                    self.editForm.sector = localProp.sector || '';
                    self.editForm.road_number = localProp.road_number || '';
                    self.editForm.road_width_ft = localProp.road_width_ft || 20;
                    self.editForm.plot_number = localProp.plot_number || '';
                    self.editForm.holding_number = localProp.holding_number || '';
                    var vis = localProp.address_visibility || {};
                    if (typeof vis === 'string') {
                        try { vis = JSON.parse(vis); } catch(e) {
                            try { vis = JSON.parse(vis.replace(/\"/g, '"')); } catch(e2) { vis = {}; }
                        }
                    }
                    self.editForm.address_visibility = {
                        block: vis.block !== false,
                        sector: vis.sector !== false,
                        road_number: vis.road_number !== false,
                        road_width_ft: vis.road_width_ft !== false,
                        plot_number: vis.plot_number !== false,
                        holding_number: vis.holding_number !== false
                    };
                }

                fetch('{{ route("buyer.dashboard") }}/property/' + propId, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    self.loadingProperty = false;
                    if (res.success) {
                        var p = res.data.property;
                        self.editForm.id = p.id;
                        self.editForm.property_code = p.property_code;
                        self.editForm.type = p.type;
                        self.editForm.title = p.title || '';
                        self.editForm.description = p.description || '';
                        self.editForm.fb_caption = p.fb_caption || '';
                        self.editForm.post_to_facebook = false;
                        self.editForm.price = p.price || '';
                        self.editForm.block = p.block || 'A';
                        self.editForm.sector = p.sector || '';
                        self.editForm.road_number = p.road_number || '';
                        self.editForm.road_width_ft = p.road_width_ft || 20;
                        self.editForm.plot_number = p.plot_number || '';
                        self.editForm.holding_number = p.holding_number || '';
                        var vis = p.address_visibility || {};
                        if (typeof vis === 'string') {
                            try { vis = JSON.parse(vis); } catch(e) {}
                        }
                        self.editForm.address_visibility = {
                            block: vis.block !== false,
                            sector: vis.sector !== false,
                            road_number: vis.road_number !== false,
                            road_width_ft: vis.road_width_ft !== false,
                            plot_number: vis.plot_number !== false,
                            holding_number: vis.holding_number !== false
                        };
                        self.editForm.facing = p.facing || 'south';
                        self.editForm.google_map_pin_url = p.google_map_pin_url || '';
                        self.editForm.latitude = p.latitude || '';
                        self.editForm.longitude = p.longitude || '';
                        self.editForm.is_negotiable = (p.is_negotiable == 1) ? 1 : 0;
                        self.editForm.owner_name = p.owner_name || '';
                        self.editForm.owner_phone = p.owner_phone || '';

                        var feats = p.other_features || [];
                        if (typeof feats === 'string') {
                            try { feats = JSON.parse(feats); } catch (e) { feats = []; }
                        }
                        if (Array.isArray(feats)) {
                            self.editForm.other_features = feats.map(function(f) {
                                if (typeof f === 'string') return { label: f, checked: true };
                                return { label: f.label || '', checked: f.checked !== false };
                            });
                        } else {
                            self.editForm.other_features = [];
                        }

                        if (p.land) {
                            self.editForm.katha_size = p.land.katha_size || '';
                            self.editForm.land_type = p.land.land_type || 'residential';
                            self.editForm.boundary_wall = (p.land.boundary_wall_done == 1 || p.land.boundary_wall == 1) ? 1 : 0;
                            self.editForm.soil_filling = p.land.soil_filling_status || p.land.soil_filling || 'ready';
                            self.editForm.corner_plot = (p.land.is_corner == 1 || p.land.corner_plot == 1) ? 1 : 0;
                            self.editForm.lake_facing = (p.land.is_lake_facing == 1 || p.land.lake_facing == 1) ? 1 : 0;
                            self.editForm.front_road_width_ft = p.land.front_road_width_ft || p.road_width_ft || 20;
                        }
                        if (p.flat) {
                            self.editForm.flat_size_sqft = p.flat.flat_size_sqft || '';
                            self.editForm.floor_number = p.flat.floor_number || 1;
                            self.editForm.total_floors = p.flat.total_floors || 6;
                            self.editForm.bedrooms = p.flat.bedrooms || 3;
                            self.editForm.bathrooms = p.flat.bathrooms || 3;
                            self.editForm.balconies = p.flat.balconies || 2;
                            self.editForm.has_parking = (p.flat.parking_available == 1 || p.flat.has_parking == 1) ? 1 : 0;
                            self.editForm.handover_status = p.flat.completion_status || p.flat.handover_status || 'ready';
                            self.editForm.handover_year = p.flat.handover_year || new Date().getFullYear();
                            self.editForm.down_payment = p.flat.down_payment || '';
                        }
                        if (p.share) {
                            self.editForm.project_name = p.share.project_name || '';
                            self.editForm.developer_name = p.share.developer_name || '';
                            self.editForm.land_size_katha = p.share.land_size_katha || '';
                            self.editForm.building_stories = p.share.building_stories || '';
                            self.editForm.flat_size_sqft = p.share.flat_size_sqft || '';
                            self.editForm.bedrooms = p.share.bedrooms || '';
                            self.editForm.bathrooms = p.share.bathrooms || '';
                            self.editForm.balconies = p.share.balconies || '';
                            self.editForm.units_per_floor = p.share.units_per_floor || '';
                            self.editForm.total_shares = p.share.total_shares || '';
                            self.editForm.available_shares = p.share.available_shares || '';
                            self.editForm.has_parking = (p.share.has_parking == 1) ? 1 : 0;
                            self.editForm.has_drawing_dining = (p.share.has_drawing_dining == 1) ? 1 : 0;
                            self.editForm.has_family_living = (p.share.has_family_living == 1) ? 1 : 0;
                            self.editForm.has_lift = (p.share.has_lift == 1) ? 1 : 0;
                            self.editForm.has_generator = (p.share.has_generator == 1) ? 1 : 0;
                            self.editForm.has_substation = (p.share.has_substation == 1) ? 1 : 0;
                            self.editForm.construction_start_date = p.share.construction_start_date || '';
                            self.editForm.construction_duration = p.share.construction_duration || '';
                            self.editForm.current_progress = p.share.current_progress || '';
                            self.editForm.location_highlights = p.share.location_highlights || '';
                        }
                        if (p.commercial) {
                            self.editForm.commercial_type = p.commercial.commercial_type || 'shop';
                            self.editForm.market_name = p.commercial.market_name || '';
                            self.editForm.shop_number = p.commercial.shop_number || '';
                            self.editForm.size_sqft = p.commercial.size_sqft || '';
                            self.editForm.price_per_sqft = p.commercial.price_per_sqft || '';
                            self.editForm.floor_number = p.commercial.floor_number || 1;
                            self.editForm.total_floors = p.commercial.total_floors || 5;
                            self.editForm.frontage_ft = p.commercial.frontage_ft || '';
                            self.editForm.ceiling_height_ft = p.commercial.ceiling_height_ft || '';
                            self.editForm.handover_status = p.commercial.completion_status || 'ready';
                            self.editForm.handover_year = p.commercial.handover_year || new Date().getFullYear();
                            self.editForm.monthly_rent_yield = p.commercial.monthly_rent_yield || '';
                            self.editForm.washroom_type = p.commercial.washroom_type || 'common';
                            self.editForm.has_parking = (p.commercial.parking_available == 1 || p.commercial.has_parking == 1) ? 1 : 0;
                            self.editForm.has_lift = (p.commercial.has_lift == 1) ? 1 : 0;
                            self.editForm.has_escalator = (p.commercial.has_escalator == 1) ? 1 : 0;
                            self.editForm.has_generator = (p.commercial.has_generator == 1) ? 1 : 0;
                            self.editForm.has_central_ac = (p.commercial.has_central_ac == 1) ? 1 : 0;
                            self.editForm.has_cctv = (p.commercial.has_cctv == 1) ? 1 : 0;
                            self.editForm.has_fire_safety = (p.commercial.has_fire_safety == 1) ? 1 : 0;
                            self.editForm.is_corner = (p.commercial.is_corner == 1) ? 1 : 0;
                            self.editForm.is_main_road_facing = (p.commercial.is_main_road_facing == 1) ? 1 : 0;
                        }

                        self.mediaList = res.data.media || [];
                        var ytItem = self.mediaList.find(function(m) { return m.media_type === 'video_embed'; });
                        self.editForm.youtube_video_url = ytItem ? (ytItem.raw_path || ytItem.file_path) : '';
                    } else {
                        alert(res.message || 'Error loading property');
                        self.editModalOpen = false;
                    }
                })
                .catch(function() {
                    self.loadingProperty = false;
                    alert('Server error loading property');
                });
            },

            saveProperty() {
                var self = this;
                self.savingProperty = true;
                self.editMsg = '';
                var fd = new FormData();
                fd.append('_token', '{{ csrf_token() }}');
                Object.entries(self.editForm).forEach(function(entry) {
                    var k = entry[0], v = entry[1];
                    if (k === 'other_features') {
                        fd.append(k, JSON.stringify(v || []));
                    } else if (k === 'address_visibility') {
                        fd.append(k, JSON.stringify(v || {}));
                    } else if (v !== null && v !== undefined) {
                        fd.append(k, typeof v === 'boolean' ? (v ? 1 : 0) : v);
                    }
                });

                fetch('{{ route("buyer.dashboard") }}/property/' + self.editForm.id, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: fd
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    self.savingProperty = false;
                    self.editSuccess = res.success;
                    self.editMsg = res.message || (res.success ? 'সফলভাবে সেভ হয়েছে!' : 'ত্রুটি!');
                    if (res.success) {
                        var target = self.myPropertiesList.find(function(p) { return p.id === self.editForm.id; });
                        if (target) {
                            target.title = self.editForm.title;
                            target.price = parseFloat(self.editForm.price);
                            target.block = self.editForm.block;
                            target.plot_number = self.editForm.plot_number;
                            target.road_width_ft = self.editForm.road_width_ft;
                            target.holding_number = self.editForm.holding_number;
                            target.address_visibility = JSON.stringify(self.editForm.address_visibility || {});
                            var pr = target.price;
                            if (pr >= 10000000) target.formatted_price = '৳' + (pr/10000000).toFixed(2) + ' কোটি';
                            else target.formatted_price = '৳' + (pr/100000).toFixed(2) + ' লাখ';
                        }
                        setTimeout(function() { self.editMsg = ''; }, 3500);
                    }
                })
                .catch(function() {
                    self.savingProperty = false;
                    self.editSuccess = false;
                    self.editMsg = 'সার্ভারে যোগাযোগ করা যায়নি।';
                });
            },

            uploadNewMedia(event) {
                var self = this;
                var files = event.target.files;
                if (!files || files.length === 0) return;
                self.uploadingMedia = true;
                var fd = new FormData();
                fd.append('_token', '{{ csrf_token() }}');
                for (var i = 0; i < files.length; i++) {
                    fd.append('new_images[]', files[i]);
                }

                fetch('{{ route("buyer.dashboard") }}/property/' + self.editForm.id + '/media', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: fd
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    self.uploadingMedia = false;
                    event.target.value = '';
                    if (res.success) {
                        self.mediaList = res.data.media || [];
                    } else {
                        alert(res.message || 'ছবি আপলোড ব্যর্থ হয়েছে');
                    }
                })
                .catch(function() {
                    self.uploadingMedia = false;
                    event.target.value = '';
                    alert('সার্ভার এরর');
                });
            },

            deleteMedia(mediaId) {
                var self = this;
                if (!confirm('আপনি কি নিশ্চিত এই ছবিটি ডিলিট করতে চান?')) return;
                var fd = new FormData();
                fd.append('_token', '{{ csrf_token() }}');

                fetch('{{ route("buyer.dashboard") }}/property/' + self.editForm.id + '/media/' + mediaId + '/delete', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: fd
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        self.mediaList = res.data.media || [];
                    } else {
                        alert(res.message || 'ডিলিট ব্যর্থ হয়েছে');
                    }
                });
            },

            setCoverMedia(mediaId) {
                var self = this;
                var fd = new FormData();
                fd.append('_token', '{{ csrf_token() }}');

                fetch('{{ route("buyer.dashboard") }}/property/' + self.editForm.id + '/media/' + mediaId + '/cover', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: fd
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        self.mediaList = res.data.media || [];
                    } else {
                        alert(res.message || 'কভার সেট ব্যর্থ হয়েছে');
                    }
                })
                .catch(function() {
                    alert('কভার সেট ব্যর্থ হয়েছে');
                });
            }
        };
};

if (window.Alpine) {
    window.Alpine.data('admBuyerDashboard', window.admBuyerDashboard);
} else {
    document.addEventListener('alpine:init', function() {
        window.Alpine.data('admBuyerDashboard', window.admBuyerDashboard);
    });
}
</script>
