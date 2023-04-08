// ker aplikacije ne sprejema JSON potem vuejs emulira json in pošlje kot navadno polje
Vue.http.options.emulateJSON = true;

// select2 direktiva
Vue.directive('select', {
    twoWay: true,
    priority: 1000,

    params: ['options'],

    bind: function () {
        var that = this;


        $(this.el).select2({
            width: '100%'
        }).on('change', function () {
            that.set(this.value)
        });

    },
    update: function (value) {
        $(this.el).val(value).trigger('change');
    },
    unbind: function () {
        $(this.el).off().select2('destroy')
    }
});



// Definiramo globalne spremenljivke za Vuejs
var gradnjaHierarhijeApp = '';
var hierarhijaApp = '';

$(document).ready(function () {
    if (document.querySelector('#hierarhija-app')) {
        hierarhijaApp = new Vue({
            el: '#hierarhija-app',
            data: {
                novaHierarhijaSt: 1, // številka prve ravni je vedno default 1, in pomeni da še nimamo nobenega vpisa ravni
                inputNivo: [],
                anketaId: $('#srv_meta_anketa_id').val(),
                sifrant: '',
                imeHierarhije: {
                    shrani: '',
                    aktivna: '',
                    index: '-1',
                    id: '-1',
                    urejanje: false,
                    editTitle: false
                },
                prikaziImeZaShranjevanje: false,
                shranjenaHierarhija: [
                    {
                        id: 'default',
                        ime: 'Hierarhija Šolski center',
                        anketa: '',
                        stUporabnikov: 0,
                        hierarhija: [
                            {
                                st: 1,
                                ime: 'Šolski center',
                                sifranti: [
                                    {ime: 'Ljubljana'},
                                    {ime: 'Maribor'},
                                    {ime: 'Koper'}
                                ]
                            },
                            {
                                st: 2,
                                ime: 'Šola',
                                sifranti: [
                                    {ime: 'Gimnazija'},
                                    {ime: 'Poklicna šola'}
                                ]
                            },
                            {
                                st: 3,
                                ime: 'Program',
                                sifranti: [
                                    {ime: 'Gimnazijec'},
                                    {ime: 'Fizik'}
                                ]
                            },
                            {
                                st: 4,
                                ime: 'Letnik',
                                sifranti: [
                                    {ime: '1. letnik'},
                                    {ime: '2. letnik'},
                                    {ime: '3. letnik'},
                                    {ime: '4. letnik'}
                                ]
                            },
                            {
                                st: 5,
                                ime: 'Razred',
                                sifranti: [
                                    {ime: 'a'},
                                    {ime: 'b'},
                                    {ime: 'c'},
                                    {ime: 'd'}
                                ]
                            },
                            {
                                st: 6,
                                ime: 'Predmet',
                                sifranti: [
                                    {ime: 'mat'},
                                    {ime: 'fiz'},
                                    {ime: 'slo'},
                                    {ime: 'geo'}
                                ]
                            }
                        ]
                    },
                    {
                        id: 'default',
                        ime: 'Šola',
                        anketa: '',
                        stUporabnikov: 0,
                        hierarhija: [
                            {
                                st: 1,
                                ime: 'Letnik',
                                sifranti: [
                                    {ime: '1. letnik'},
                                    {ime: '2. letnik'},
                                    {ime: '3. letnik'},
                                    {ime: '4. letnik'}
                                ]
                            },
                            {
                                st: 2,
                                ime: 'Razred',
                                sifranti: [
                                    {ime: 'a'},
                                    {ime: 'b'},
                                    {ime: 'c'},
                                    {ime: 'd'}
                                ]
                            },
                            {
                                st: 3,
                                ime: 'Predmet',
                                sifranti: [
                                    {ime: 'mat'},
                                    {ime: 'fiz'},
                                    {ime: 'slo'},
                                    {ime: 'geo'}
                                ]
                            }
                        ]
                    }
                ],
                defaultHierarhija: '',
                // omogočimo predogled hierarhije
                previewHierarhije: {
                    vklop: true,
                    input: [],
                    ime: '',
                    index: '',
                    id: '',
                    uporabniki: 0
                },

                imeNivoja: '',
                brisanjeDropdownMenija: [], // ali je opcija za meni vklopljena ali izklopljena
                vklopiUrejanje: true, // Vklopimo možnost urejanja preimenovanja
                vpisanaStruktura: false, // pove nam če je vpisana struktura oz. so dodani uporabniki na hierarhijo
                kopirajTudiUporabnike: 0, // iz seznama shranjenih hierarhij kopiramo tudi uporabnike/strukturo, če je seveda shranjena
            },

            // watch:{
            //     'imeHierarhije.shrani':function(val, oldVal){
            //         this.imeHierarhije.aktivna = val;
            //     }
            // },
            ready: function () {
                // Pridobi število nivojev
                this.pridobiStNivojev();

                var that = this;
                // Pridobi nivoje in podatke
                this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=json_nivoji_podatki').success(function (data) {
                    if (data != 'undefined' && data.length > 0) {
                        $.each(data, function (index, value) {
                            that.inputNivo.push(value);
                        });
                    }
                });

                // Pridobimo shranjene hierarhije v bazi
                this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=json_shranjene_hierarhije').success(function (data) {
                    if (data != 'undefined' && data.length > 0) {
                        $.each(data, function (index, value) {
                            that.shranjenaHierarhija.push(value);
                        });
                    }
                });


                // pridobimo vse nastavitve iz baze
                this.vseNastavitveIzBaze();
            },

            // Pridobimo trenutno število nivojev in dodamo novega
            methods: {
                // Omogoči možnost preimenovanja ankete
                editTitleToogle: function () {
                    return this.imeHierarhije.editTitle = !this.imeHierarhije.editTitle;
                },

                dodajNivoHierarhije: function (st) {
                    var that = this;
                    var ime = this.imeNivoja || 'nivo';
                    var st = this.novaHierarhijaSt;
                    this.imeNivoja = '';

                    // POST request
                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=post_nivoji', {
                        nivo: st,
                        ime: ime
                    }).success(function (data) {
                        // ko dobimo id od ravni potem napolnimo dom element inputNivo
                        that.inputNivo.push({
                            st: st,
                            ime: ime,
                            id: data,
                            sifranti: []
                        });

                        // posodobimo število nivojev
                        that.pridobiStNivojev();

                    });

                },

                odstraniNivoHierarhije: function (index, id) {
                    var st = this.inputNivo[index].st;

                    this.inputNivo.forEach(function (obj) {
                        if (obj.st > st)
                            obj.st = obj.st - 1;
                    });

                    var that = this;


                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=brisi_nivo_hierarhija', {
                        id_nivoja: id
                    }).then(function (response) {
                        if (response.status == 200 && response.data == 0) {
                            swal({
                                title: "Napaka!",
                                text: "Omenjen nivo ni mogoče izbrisati, ker je že uporabljen pri izgradnji hierarhije.",
                                type: "warning",
                                confirmButtonText: "OK"
                            });
                        } else {
                            that.inputNivo.splice(index, 1);
                            that.novaHierarhijaSt = (that.novaHierarhijaSt - 1);
                        }
                    });
                },

                // izbrišemo vse ravni v hierarhiji, da lahko uporabnik na novo ustvarja
                izbrisiCelotnoHierarhijo: function () {

                    // Prejšno hierarhijo vedno shranimo
                    if (this.inputNivo.length > 0)
                        this.shraniTrenutnoHierarhijo();

                    // Če uporabnik ne vpiše imena potem obstoječo ne brišemo
                    if (this.pobrisiHierarhijoInZacniNovo()) {
                        // Vse spremenljivke postavimo na 0
                        this.imeHierarhije = {
                            aktivna: '',
                            shrani: '',
                            index: '-1',
                            id: '-1'
                        };

                        this.previewHierarhije.vklop = false;
                    }
                },

                // PObrišemo trenutno aktivno hierarhijo in začnemo novo, ki jo tudi shranimo za kasnejši preklic
                pobrisiHierarhijoInZacniNovo: function () {
                    var that = this;

                    //# V kolikor dela novo hierarhijo potem vedno prikažemo možnost za vpis imena
                    swal({
                        title: "Nova hierarhija",
                        text: "Vpišite ime nove hierarhije:",
                        type: "input",
                        animation: "slide-from-top",
                        closeOnConfirm: false,
                        closeOnCancel: true,
                        showCancelButton: true,
                        cancelButtonText: 'Prekliči',
                        allowOutsideClick: true,
                        inputPlaceholder: "Primer: Hierarhija šola"
                    }, function (inputValue) {
                        if (inputValue === false) return false;
                        if (inputValue === "") {
                            swal.showInputError("Ime hierarhije je obvezno!");
                            return false
                        }

                        //# Pobrišemo vse ravni in vso trenutno hierarhij v kolikor vpiše novo
                        that.$http.post('ajax.php?anketa=' + that.anketaId + '&t=hierarhija-ajax&a=izbrisi_vse_ravni');

                        // Ime hierarhije shranimo v vue spremenljivko
                        that.getSaveOptions('aktivna_hierarhija_ime', inputValue);
                        that.imeHierarhije.shrani = inputValue;

                        // Ime hierarhije shranimo tudi v srv_hierarhija_shrani, da dobimo ID vnosa, kamor potem shranjujemo json podatke z vsemi šifranti in nivoji
                        that.$http.post('ajax.php?anketa=' + that.anketaId + '&t=hierarhija-ajax&a=shrani_hierarhijo', {
                            ime: inputValue,
                            hierarhija: null
                        }).success(function (id) {
                            // shranimo tudi ID hierarhije
                            that.getSaveOptions('srv_hierarhija_shrani_id', id);
                        });


                        location.reload();
                    });


                },

                // Dodamo šifrant k ustreznemu nivoju/ravni
                dodajSifrant: function (index, idNivoja) {
                    var text = $('[data-nivo="' + idNivoja + '"]').val();

                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=post_sifranti', {
                        idNivoja: idNivoja,
                        imeSifranta: text
                    }).success(function (data) {
                        this.inputNivo[index].sifranti.push({
                            ime: text
                        });

                        $('[data-nivo="' + idNivoja + '"]').val('');

                        var opcije = '';
                        $.each(data, function (index, value) {
                            opcije += '<option value = "#">' + value + '</option>';
                        });

                        $('#nivo-' + idNivoja + ' td:eq( 1 )').html('<select name="nivo" data-opcije="' + idNivoja + '">' + opcije + '</select>');
                    });

                },

                brisiSifrant: function (idNivoja) {
                    var that = this;

                    // Toogle spremenljivka, ki prikaže urejanje ali drop down meni
                    if (typeof this.brisanjeDropdownMenija[idNivoja] == 'undefined')
                        this.brisanjeDropdownMenija[idNivoja] = false;

                    this.brisanjeDropdownMenija[idNivoja] = !this.brisanjeDropdownMenija[idNivoja];

                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=brisi_sifrante', {
                        idNivoja: idNivoja,
                    }).success(function (data) {

                        if (that.brisanjeDropdownMenija[idNivoja]) {
                            var opcije = '<div class="sifranti-razmik"><ul>';
                            $.each(data, function (index, value) {
                                opcije += '<li class="sifranti-brisanje" data-sifrant="' + value.id + '"><span class="icon brisi-x" onclick="izbrisiSifrant(' + value.id + ')"></span>' + value.ime + '</li>';
                            });
                            opcije += '</ul></div>';

                            $('#nivo-' + idNivoja + ' td:eq( 1 )').html(opcije);
                        } else {
                            $('[data-nivo="' + idNivoja + '"]').val('');

                            var opcije = '';
                            $.each(data, function (index, value) {
                                opcije += '<option value = "#">' + value.ime + '</option>';
                            });

                            $('#nivo-' + idNivoja + ' td:eq( 1 )').html('<select name="nivo" data-opcije="' + idNivoja + '">' + opcije + '</select>');
                        }

                    });

                },

                posodobiUnikatnega: function (id, obj) {
                    if (obj.unikaten == 0) {
                        obj.unikaten = 1;
                    } else {
                        obj.unikaten = 0;
                    }

                    $.post("ajax.php?anketa=" + this.anketaId + "&t=hierarhija-ajax&a=popravi_nivo_hierarhija", {
                        id_nivoja: id,
                        unikaten: obj.unikaten
                    });
                },

                // posodobi ime labele nivoja
                preimenujLabeloNivoja: function (id) {
                    this.$http.post("ajax.php?anketa=" + this.anketaId + "&t=hierarhija-ajax&a=popravi_nivo_hierarhija", {
                        id_nivoja: id,
                        besedilo: $('[data-labela="' + id + '"]').text()
                    });
                },

                // Pridobimo število nivojev, ki je vpisano za izbrano anketo
                pridobiStNivojev: function () {
                    var that = this;
                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=st_nivojev').success(function (data) {
                        that.novaHierarhijaSt = (data + 1);

                        if (data > 0)
                            that.previewHierarhije.vklop = false;

                    });
                },

                // Shranimo trenutno izdelano hierarhijo
                shraniTrenutnoHierarhijo: function (shraniPodIstiId) {
                    // Če želimo izvesti update ali create new
                    var shraniPodIstiId = shraniPodIstiId || false;

                    // V kolikor samo uporabimo checkbox in je še vedno isto potem naredimo update
                    if (this.imeHierarhije.shrani == this.imeHierarhije.aktivna)
                        shraniPodIstiId = true;

                    // preverimo, če je shranjena struktura potem
                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=pridobi-shranjeno-hierarhijo-bool', {
                        id: this.imeHierarhije.id,
                        polje: 'struktura',
                    }).then(function (response) {

                        // UPDATE se vedno zgodi, kadar gremo naprej
                        if (shraniPodIstiId && this.imeHierarhije.index > 1 && this.imeHierarhije.index != 'default') {
                            return this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=update-aktivno-hierarhijo', {
                                id: this.imeHierarhije.id,
                                hierarhija: JSON.stringify(this.inputNivo)
                            }).success(function () {
                                // našo trenutno hierarhijo shranimo tudi v dom, da v kolikor uporabnik še enkrat izbere isto hierarhijo, da se mu naložijo isti elementi
                                this.shranjenaHierarhija[this.imeHierarhije.index].hierarhija = JSON.stringify(this.inputNivo);
                            });
                        }

                        // Hierarhijo shranimo na novo

                        // če hierarhije ne poimenujemo potem dobi privzeto ime Hierarhija in čas kopiranja ali pa ostoječe ime in čas kopiranja (Šola, Hierarhija Šolski center)
                        if (!this.prikaziImeZaShranjevanje) {
                            // Če je že kopija kakšne od preh hierarhije potem dobi obstoječe ime in  uro
                            var time = new Date();
                            if (this.imeHierarhije.aktivna.length > 0) {
                                //  ime_H:i:s"
                                var sekunde = ('0' + time.getSeconds()).slice(-2);
                                var minute = ('0' + time.getMinutes()).slice(-2);
                                var ure = ('0' + time.getHours()).slice(-2);

                                this.imeHierarhije.shrani = this.imeHierarhije.aktivna + '_' + ure + ':' + minute + ':' + sekunde;
                            } else {
                                // Drugače pa "Hierarhija - H:i:s"
                                this.imeHierarhije.shrani = 'Hierarhija - ' + time.getHours() + ':' + time.getMinutes() + ':' + time.getSeconds();
                            }
                        }

                        this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=shrani_hierarhijo', {
                            ime: this.imeHierarhije.shrani,
                            hierarhija: JSON.stringify(this.inputNivo)
                        }).then(function (responseShrani) {
                            this.imeHierarhije.aktivna = this.imeHierarhije.shrani

                            // SHRANJENO HIERARHIJO shranimo tudi v spremenljivko za kasnejši preklic
                            this.shranjenaHierarhija.push({
                                id: responseShrani.data,
                                ime: this.imeHierarhije.shrani,
                                hierarhija: (typeof this.inputNivo == 'string' ? JSON.stringify(this.inputNivo) : this.inputNivo),
                                anketa: this.anketaId,
                                dom: true,
                            });

                            this.imeHierarhije.index = (this.shranjenaHierarhija.length - 1);

                            // shranimo tudi ID hierarhije
                            this.getSaveOptions('srv_hierarhija_shrani_id', responseShrani.data);
                            this.imeHierarhije.id = responseShrani.data;
                        });

                        // Ime shranjene hierarhije shranimo tudi kot aktivno hierarhijo
                        this.getSaveOptions('aktivna_hierarhija_ime', this.imeHierarhije.shrani);
                    });


                },

                /*
                 * Gumb za premikanje naprej
                 */
                premikNaprej: function (ime) {

                    if (ime == 'uredi-uporabnike') {
                        this.shraniTrenutnoHierarhijo(false, true);

                        // Preusmerimo na urejanje uporabnikov in naredimo cel reload ter pobrišemo cache
                        window.location.replace(location.origin + location.pathname + "?anketa=" + this.anketaId + "&a=hierarhija_superadmin&m=uredi-uporabnike");
                    }
                },

                /*
                 * Uporabimo shranjeno hierarhijo iz seznama
                 */
                uporabiShranjenoHierarhijo: function (index, id, uporabniki) {
                    var that = this;

                    // Tukaj moram imeti podatke še o starih stvareh
                    this.imeHierarhije.id = id;
                    this.uporabnikiZaKopijo = uporabniki || 0;

                    if (this.vpisanaStruktura)
                        return swal({
                            title: "Opozorilo!",
                            text: "Pri omenjeni strukturi hierarhije so že dodani uporabniki in nove hierarhije ni več mogoče izbrati, lahko samo dopolnjujete obstoječo.",
                            type: "warning",
                            confirmButtonText: "Zapri"
                        });

                    // Kadar še nimamo vpisane nobene ravni
                    if (this.novaHierarhijaSt == 1)
                        return that.posodobiHierarhijo(index, id);

                    swal({
                        title: "Kopiranje hierarhije",
                        text: "Z nadaljevanjem se bo hierarhija skopirala v novo ime, obstoječa pa se bo avtomatsko shranila pod dosedanje ime.",
                        type: "info",
                        showCancelButton: true,
                        cancelButtonText: "Ne",
                        confirmButtonText: "Da, nadaljuj."
                    }, function (shrani) {

                        if (shrani) {
                            // V kolikor želi uporabnik shraniti trenutno hierarhijo in pustimo index kot je
                            that.shraniTrenutnoHierarhijo(true);

                            setTimeout(function () {
                                Vue.nextTick(function () {
                                    // Izberemo novo hierarhijo
                                    that.posodobiHierarhijo(index, id);
                                });
                            }, 100);

                        }

                    });
                },

                // Preglej shranjeno hierarhijo in ne shrani v bazo
                pregledShranjeneHierarhije: function (index, id, uporabniki) {
                    // Nastavitve trenutne strukture na katero je kliknil uporabnik shranimo v predogled, da se lahko uporabi v kolikor bi uporabnik želel uporabiti omenjeno hierarhijo
                    this.previewHierarhije = {
                        vklop: true,
                        ime: this.shranjenaHierarhija[index].ime,
                        index: index,
                        id: id,
                        uporabniki: uporabniki
                    };


                    if (typeof this.shranjenaHierarhija[index].hierarhija == 'object')
                        this.previewHierarhije.input = this.shranjenaHierarhija[index].hierarhija;
                    else
                        this.previewHierarhije.input = JSON.parse(this.shranjenaHierarhija[index].hierarhija);
                },

                // Izklopimo predogled hierarhije
                izklopiPredogled: function () {
                    this.previewHierarhije = {
                        vklop: false,
                        ime: '',
                        index: '',
                        id: '',
                        uporabniki: 0,
                        input: []
                    };
                },

                // Uporabnik je iz predogleda izbral željeno hierarhijo, ki se bo aktivirala
                aktivirajIzbranoHierarhijo: function () {
                    this.uporabiShranjenoHierarhijo(this.previewHierarhije.index, this.previewHierarhije.id, this.previewHierarhije.uporabniki);
                },

                posodobiHierarhijo: function (index, id) {
                    var that = this;

                    // dodamo active class
                    this.imeHierarhije.index = index;

                    // Če urejamo hierarhijo potem nič ne urejamo sql baze in klik na ime hierarhije omogoči samo preimenovanje in brisanje
                    if (this.imeHierarhije.urejanje)
                        return '';

                    // preimenujemo Hierarhijo
                    this.imeHierarhije.aktivna = this.shranjenaHierarhija[index].ime;

                    // // shranimo ime hierarhije in trenuten id izbrane hierarhije v opcije
                    // this.getSaveOptions('aktivna_hierarhija_ime', this.imeHierarhije.aktivna);
                    // this.getSaveOptions('srv_hierarhija_shrani_id', id);

                    // Kadar prikličemo hierarhijo, ki je prazna, smo izbrali samo ime potem nič ne vrnemo, vse postavimo na nič
                    if (this.shranjenaHierarhija[index].hierarhija == '') {
                        this.inputNivo = [];
                        this.novaHierarhijaSt = 1;
                        // naloži šifrante, ker imamo šifrante v JSON.stringfy moramo anredite revers v object in če je object potem samo zapišemo v spremenljivko, drugače pa delamo reverse
                    } else if ((index < 2 || id === 'default') && typeof this.shranjenaHierarhija[index].hierarhija == 'object') {
                        this.inputNivo = this.shranjenaHierarhija[index].hierarhija;
                    } else {
                        this.inputNivo = JSON.parse(this.shranjenaHierarhija[index].hierarhija);
                    }


                    // prevzeto ne kopira uporabnikov, samo če pote če potrdi iz seznama
                    this.kopirajTudiUporabnike = 0;

                    // pošljemo ravni in nivoje ter shranimo vse potrebno v
                    if (this.uporabnikiZaKopijo == 1) {
                        setTimeout(function () {
                            swal({
                                title: "Opozorilo!",
                                text: "Ali želite kopirati tudi strukturo uporabnikov?",
                                type: "info",
                                showCancelButton: true,
                                cancelButtonText: "Ne",
                                confirmButtonText: "Da, tudi uporabnike."
                            }, function (shrani) {

                                if (shrani)
                                    that.kopirajTudiUporabnike = 1;

                                Vue.nextTick(function () {
                                    that.$http.post('ajax.php?anketa=' + that.anketaId + '&t=hierarhija-ajax&a=obnovi-hierarhijo', {
                                        hierarhija: that.inputNivo,
                                        uporabniki: that.kopirajTudiUporabnike,
                                        id: id
                                    }).success(function (data) {
                                        that.inputNivo = [];

                                        if (data != 'undefined' && data != '' && data.length > 0)
                                            $.each(data, function (index, value) {
                                                that.inputNivo.push(value);
                                            });

                                        that.shraniTrenutnoHierarhijo();

                                        // posodobimo število nivojev
                                        that.pridobiStNivojev();

                                    });
                                });

                            });
                        }, 100);
                    } else {
                        Vue.nextTick(function () {
                            that.$http.post('ajax.php?anketa=' + that.anketaId + '&t=hierarhija-ajax&a=obnovi-hierarhijo', {
                                hierarhija: that.inputNivo,
                                uporabniki: that.kopirajTudiUporabnike,
                                id: id
                            }).success(function (data) {
                                that.inputNivo = [];

                                if (data != 'undefined' && data != '' && data.length > 0)
                                    $.each(data, function (index, value) {
                                        that.inputNivo.push(value);
                                    });

                                that.shraniTrenutnoHierarhijo();

                                // posodobimo število nivojev
                                that.pridobiStNivojev();

                            });
                        });

                    }

                },

                // shrani ali pridobi opcije iz baze
                getSaveOptions: function (option, value, response) {
                    if (typeof value != 'undefined' && typeof response == 'undefined')
                        response = 'save';

                    if (typeof value == 'undefined' && typeof response == 'undefined')
                        response = 'get';

                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=hierarhija-options&m=' + response, {
                        option_name: option || '',
                        option_value: value || ''
                    }, function (data) {
                        return data;
                    });
                },

                // ko zapustimo urejanje/preimenovanje potem spremenimo tudi dom
                preimenujHierarhijo: function (index, id) {
                    var ime = $.trim($('.h-ime-shranjeno.editable-hierarhija').html());

                    //odstranimo html tag
                    var div = document.createElement('div');
                    div.innerHTML = ime;
                    ime = $.trim(div.innerText);

                    var ime_dom = this.shranjenaHierarhija[index].ime;

                    // V kolikor je bila preimenova aktivna anketa moramo tudi v bazi med opcijami preimenovati
                    if (this.imeHierarhije.aktivna == ime_dom)
                        this.getSaveOptions('aktivna_hierarhija_ime', ime);

                    // v kolikor je zbrisano celotno ime ponovno damo na default
                    if (id == 'default' || ime.length == 0 || this.shranjenaHierarhija[index].ime.length == 0)
                        return $('.h-ime-shranjeno.active').html(ime_dom);

                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=preimenuj-hierarhijo', {
                        id: id,
                        ime: ime
                    }, function () {
                        //v kolikor smo v bazi uspešno preimenovali potem tudi v naši spremenljivki preimenujemo
                        this.shranjenaHierarhija[index].ime = ime;
                    });
                },

                izbrisiShranjenoHierarhijo: function (index, id) {
                    if (id == 'default' || id == this.imeHierarhije.id)
                        return '';

                    // post request, ki izbriše iz baze
                    var obvestilo = this.deleteHierarhijaShrani(id);

                    if (obvestilo)
                        this.shranjenaHierarhija.splice(index, 1);

                },

                // Uvoz in izviz hierarhije v CSV
                uvozHierarhije: function () {
                    $('#fade').fadeTo('slow', 1);
                    $('#vrednost_edit').html('').fadeIn('slow').load('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=uvozi-hierarhijo', function () {

                        //Vklopi nice input file
                        $("input[type=file]").nicefileinput({
                            label: 'Poišči datoteko...'
                        });

                    });
                },

                izvozHierarhije: function () {
                    this.$http.get('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=izvoz-hierarhije');
                },

                // pridobimo vse nastavitve iz baze
                vseNastavitveIzBaze: function () {
                    var that = this;

                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=hierarhija-options&m=get').success(function (data) {

                        $.each(data, function (index, value) {
                            if (index == 'aktivna_hierarhija_ime') {
                                // za prikaz naslova hierarhije
                                that.imeHierarhije.aktivna = value;

                                // polje za shranjevanje, da shrani v enako hierarhijo
                                that.imeHierarhije.shrani = value;

                                // Če imamo ime hierarhije potem nimamo predogleda
                                if (value.length > 0)
                                    that.previewHierarhije.vklop = false;

                                // that.imeHierarhije.index = (that.shranjenaHierarhija.length - 1);
                            }

                            if (index == 'admin_skrij_urejanje_nivojev')
                                that.vklopiUrejanje = (value == 'true' ? true : false);

                            if (index == 'srv_hierarhija_shrani_id') {
                                // na levi strani izbere ustrezno hierarhijo, moramo nastavit timeout, ker drugače ne pridobimo vseh hierarhij
                                setTimeout(function () {
                                    Vue.nextTick(function () {
                                        $.each(that.shranjenaHierarhija, function (i, val) {
                                            if (val.id == value) {
                                                that.imeHierarhije.index = i;
                                                that.imeHierarhije.id = value;
                                            }
                                        });
                                    });
                                }, 100);
                            }

                            // V kolikor imamo vpisano struktur
                            if (index == 'vpisana_struktura')
                                that.vpisanaStruktura = value;

                        });

                    });
                },

                posodobiOpcijeHierarhije: function () {
                    if (this.imeHierarhije.urejanje)
                        this.vseNastavitveIzBaze();
                },

                /**
                 * Če smo hierarhijo prvič aktivirali potem ponudi popup za vpis imena in shrani ime hierarhije v bazo
                 */
                hierarhijoSmoAktivirali: function () {
                    var that = this;

                    if (this.inputNivo.length == 0 && this.imeHierarhije.aktivna == '' && this.imeHierarhije.shrani == '')
                        swal({
                            title: "Nova hierarhija",
                            text: "Vpišite ime nove hierarhije:",
                            type: "input",
                            animation: "slide-from-top",
                            closeOnConfirm: false,
                            closeOnCancel: true,
                            inputPlaceholder: "Primer: Hierarhija šola"
                        }, function (inputValue) {
                            if (inputValue === false) return false;

                            if (inputValue === "") {
                                swal.showInputError("Ime hierarhije je obvezno!");
                                return false
                            }

                            // Ime hierarhije shranimo v vue spremenljivko
                            that.getSaveOptions('aktivna_hierarhija_ime', inputValue);
                            that.imeHierarhije.shrani = inputValue;
                            that.imeHierarhije.aktivna = inputValue;

                            swal.close();
                        });
                },


                /**
                 * Pobriše shranjeno hierarhijo v tabeli srv_hierarhija_shrani
                 */
                deleteHierarhijaShrani: function (id) {
                    var id = id || 0;
                    var obvestilo = false;

                    if (id == 0)
                        return console.log('brez Id-ja');

                    // post request, ki izbriše iz baze
                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=izbrisi-hierarhijo', {
                        id: id
                    }).then(function (response) {
                        if (response.data == 'success')
                            obvestilo = true;

                        return obvestilo;
                    });

                    return obvestilo;
                },

                /**
                 * Dodaj komentar k hierarhiji
                 */
                dodajKomentar: function () {
                    dodajKomentar();
                },

                /**
                 * Odpre popup za nalaganje logotipa
                 */
                logoUpload: function () {
                    uploadLogo();
                }

            }

        });
    }


    if (document.querySelector('#vue-gradnja-hierarhije')) {
        gradnjaHierarhijeApp = new Vue({
            el: '#vue-gradnja-hierarhije',
            data: {
                anketaId: $('#srv_meta_anketa_id').val(),
                pageLoadComplete: false,
                vpisanaStruktura: false, // pove nam, če je uporabnik že vpisal kakšno strukturo, da s tem zaklenemo vpis novih ravni (obstoječa struktura ne bi bila ok)
                izbran: {
                    skrij: 1,
                    sifrant: [],
                    strukturaId: [],
                    sifrantPodatki: [],
                    parent: [],
                },
                // tukaj vpišemo št. nivoja, ki je key in nato sifrante
                podatki: [],

                // V kolikor uporabnik ni superadmin/admin potem podtke, ki so nad njegovim ali enake njegovemu nivoju pridobimo kot fiksne in se jih ne da spreminjati
                fiksniPodatki: [],

                // pri vpisu oseb na ustrezni nivo
                osebe: {
                    prikazi: false,
                    nivo: 0,
                    vpisane: [], // key je številka nivoja, in potem notri imam object s podatki o osebah
                    nove: [], // key je številka nivoja in nato notri object s podatki o osebah
                    textarea: '',
                    show: [] // boolean, glede na nivo, da pokaže uporabnike pod šifranti
                },

                // podatki o uporabniku, ki ni admin
                user: {
                    struktura: [],
                    uporabnik: [],
                    dropdown: [],
                    selected: ''
                },

                // vpis emaila preko textarea
                email: {
                    napake: [],
                    opozorilo: false
                },

                elektronskiNaslovi: [{
                    email: "prvi@email.si",
                    ime: "Prvo Ime"
                }, {
                    email: "drugi@email.si",
                    ime: "Drugi email"
                }],

            },
            watch: {
                'user.selected': function (val) {

                    if (typeof val !== 'undefined' && val !== null && val.length > 0)
                        this.vpisemoUporabnikaIzDropDownMenija();

                }
            },
            computed: {},

            ready: function () {
                var that = this;

                // Pridobimo omejitve uporabnika
                this.preveriNivoInPraviceUporabnika();

                // Pridobimo vse nivoje in šifrante neglede na status uporabnika
                this.naloziVseNivojeInSifrante();

                // Ko je celoten JS in spletna stran naložena potem spremenimo select2 change event, da deluje
                document.onreadystatechange = function () {

                    // Ko je stran čisto naložena izvedemo kodo
                    if (document.readyState === 'complete') {

                        // potrebno, ker drugače v FF in IE stvar ne deluje, da je zakasnitev 300ms in se počaka potem na naslednjo spremembo
                        setTimeout(function () {
                            Vue.nextTick(function () {

                                // Prikažemo prvi nivo
                                that.pageLoadComplete = true;

                                // Select 2 event
                                $(".select2").on('change', function () {

                                    // uogtotovimo, kje smo spremenili podatek
                                    var st = that.izbran.sifrant.length;
                                    var level = $(this).attr('data-level');

                                    that.izbran.sifrant.forEach(function (value, key) {
                                        if (key > level) {
                                            for (var i = key; i < st; i++) {
                                                that.izbran.sifrantPodatki.$set(i, null);
                                                that.izbran.sifrant[i] = "";
                                            }
                                        }
                                    });

                                    // Zanka po vseh nivojih, kateri so vpisani
                                    that.izbran.sifrant.forEach(function (value, key) {
                                        if (typeof value != 'undefined' && value.length > 0 && !isNaN(value) && that.izbran.sifrant[key].length > 0) {
                                            that.preveriSifrantZaIzbranNivo(value, key)
                                        }
                                    });

                                });
                            });
                        }, 600);

                        // Dodamo še možnost helpa v kolikor obstaja
                        load_help();
                    }
                }

                // Pridobi, če so že vpisani šifranti
                this.pridobiNastavitveCeJeVpisanaStruktura();

                // Pridobimo uporabnikeza dropdown meni user
                this.pridobiUporabnikeZaDropdownList();

            },

            methods: {
                // Preverimo, če je uporabnik admin ali je uporabnik s pravicami na določenem nivoju
                preveriNivoInPraviceUporabnika: function () {
                    var that = this;

                    // preverimo pravico in pridobimo že vpisano strukturo nad uporabikom
                    this.$http.get('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=get-user-level', function (data) {
                        // pridobimo polji (uporabnik, struktura), v kolikor je admin ni podatka o strukturi
                        that.user = data;

                        if (data.uporabnik != 1 && data.struktura) {
                            // ID strukture, ki je fiksna zapišemo v spremenljivko
                            data.struktura.forEach(function (val) {
                                that.izbran.strukturaId.$set(val.izbrani.level, val.izbrani.id);

                                // Že izbrano strukturo vpišemo v sifrantiPodatki, kjer se dodajajo tudi še na novo dodani podatki
                                that.izbran.sifrantPodatki.$set(val.izbrani.level, val.izbrani);
                            });

                        }
                    });
                },

                // Naložimo vse nivoje in pripadajoče šifrante
                naloziVseNivojeInSifrante: function () {
                    var that = this;

                    // pridobi šifrante za ustrezni nivo, če ni nič izbrano potem vedno pridobi šifrante za prvi nivo
                    this.$http.get('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=get-sifranti', function (data) {
                        data.nivoji.forEach(function (val) {
                            val.sifranti = [];

                            // vpišemo nivo in pdoatke nivoja
                            that.podatki.push(val);

                            // pole $(this.el).on('change', )g nivoja vpišemo še podatke o šifrantih
                            data.sifranti.forEach(function (options) {
                                // tukaj zapišemo šifrante na ustrezen nivo, edino tukaj upoštevamo, da številka nivoja je za 1 manšja, ker če 0 pustimo potem pri prikazuso težave, nivo 1 je element 0
                                if (val.level == options.level)
                                    that.podatki[(val.level - 1)].sifranti.push(options);
                            });
                        });

                        // Max število nivojev za validacije
                        that.podatki.maxLevel = data.maxLevel;
                    });
                },

                // Preveri, če je šifrant za izbran nivo že vpisan v podatkovno bazo
                preveriSifrantZaIzbranNivo: function (sifrant, nivo) {
                    var that = this;
                    // Parent vedno vzamemo id elementa, ki je vpisan en nivo prej
                    var parent_id = (this.izbran.sifrantPodatki[nivo - 1] ? this.izbran.sifrantPodatki[nivo - 1].id : null);

                    Vue.nextTick(function () {
                        // var parent_id2 = (that.izbran.sifrantPodatki[nivo - 1] ? that.izbran.sifrantPodatki[nivo - 1].id : null);

                        that.$http.post('ajax.php?anketa=' + that.anketaId + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=preveri-sifrant-za-nivo', {
                            level: nivo,
                            hierarhija_sifranti_id: sifrant,
                            parent_id: parent_id
                        }).then(function (i) {
                            if (i.data == 0) {
                                // V kolikor omenjen id šifranta še ne obstaja v strukturi potem shranimo v polje novSifrant, da ga pri sumbitu upoštevamo
                                that.izbran.sifrantPodatki.$set(nivo, {
                                    id: null,
                                    level: nivo,
                                    hierarhija_sifranti_id: sifrant,
                                    hierarhija_ravni_id: that.podatki[nivo - 1].id,
                                    parent_id: parent_id
                                });
                            } else {
                                // shranimo na ključ, kjer je nivo celo polje
                                that.izbran.sifrantPodatki.$set(i.data.level, i.data);
                            }

                            // Preverimo, za nivo, če lahko prikažemo uporabnike
                            that.prikaziUporabnike(nivo);
                        });
                        // DOM updated
                    });

                },

                // Potrdimo vpis šifrantov, ki smo jih izbrali
                submitSifrante: function () {
                    var that = this;

                    // Preverimo, če je bil dodan kak nov elemepridobiIdSifrantovInUporabnikent
                    var prestejNove = 0;
                    this.izbran.sifrantPodatki.forEach(function (val) {
                        if (val != null && val.id == null && !isNaN(val.id))
                            prestejNove++;
                    });

                    if (prestejNove == 0)
                        return swal({
                            title: "Opozorilo!",
                            text: "<div style='text-align: left;'>Vse vrstice so že prenesene v hierarhijo:" +
                            "<ul><li>Bodisi vnesite novega učitelja in njegov predmet.</li>" +
                            "<li>Bodisi zaključite z vnosom in s klikom na gumb NAPREJ (spdaj desno) aktivirajte hierarhijo.</li></ul></div>",
                            type: "error",
                            html: true
                        });

                    var st = this.podatki.maxLevel;
                    // Če je vnešen zadnji nivo, object ni null in ni vpisanih oseb, ker na zadnjem nivo morajo biti vpisane osebe
                    if (that.izbran.sifrantPodatki[st] != null && (typeof this.osebe.nove[st] == 'undefined' || this.osebe.nove[st].length == 0))
                        return swal({
                            title: "Opozorilo!",
                            text: "Na zadnjem nivoju morate obvezno vpisati elektronski naslov osebe.",
                            type: "error"
                        });

                    // Izpišemo opozorilo, če uporabnik ni vnesel zadnjega nivoja
                    if (that.izbran.sifrantPodatki[st] == null)
                        swal({
                            title: "Opozorilo!",
                            text: "Niste vpisali zadnjega nivoja.",
                            type: "warning",
                            timer: 2000
                        });

                    // Posredujemo podatke za shranjevanje
                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=post-struktura', {
                        vnos: that.izbran.sifrantPodatki,
                        osebe: that.osebe.nove
                    }).then(function () {
                        //Tukaj moramo osvežiti vse šifrante, v dataTables in JsTree, omenjeni funkciji sta v custom.js - splošni jquery
                        tabela.ajax.reload(null, false);
                        jstree_json_data(that.anketaId, 1);

                        // Če je bil izdan zadnji nivo od vseh mogočih potem odstranimo element izbire iz zadnjega nivoja
                        if (typeof that.izbran.sifrant[that.podatki.maxLevel] != 'undefined' && that.izbran.sifrant[that.podatki.maxLevel].length > 0) {
                            // Zadnji nivo odstranimo iz select2 izbire
                            that.izbran.sifrant.splice(that.podatki.maxLevel, 1);

                            // Izbrišemo tudi vse podatke o izbranem elementu iz DOM-a
                            that.izbran.sifrantPodatki.splice(that.podatki.maxLevel, 1);

                            //postavimo spremenljivko na true, da prikaže drugačen tekst pri navodilih
                            $('.srv_hierarchy_user_help').hide();
                            $('.srv_hierarchy_user_help_sifrant_vnesen').show();
                        }

                        // Osveži podatke o vseh šifrantih, ki so izbrani in so bili na novo dodani
                        that.preveriBazoZaSifrant(null, 1);

                        // Polje z na novo dodanimi osebami se izprazni
                        that.osebe.nove = [];

                        //Odstrani besedilo Uporabnik/i iz zadnjega polja, ker ga še tako odstranimo
                        that.osebe.show.$set(st, false);

                        // Zapišemo spremembo, da je struktura vnešena
                        that.aliJeStrukturaVnesena();

                        // Shanimo celotno strukturo v string in srv_hierarhija_shrani
                        that.shraniUporabnikeNaHierarhijo();

                    });
                },

                // Klik na ikono osebe, prikaže spodaj opcijo za vpis oseb
                prikaziVnosOseb: function (level) {
                    // V kolikor kliknemo na isto ikono 2x potem uporabimo toggle opcijo
                    if (level == this.osebe.nivo)
                        return this.osebe.prikazi = !this.osebe.prikazi;

                    this.osebe.prikazi = true;
                    return this.osebe.nivo = level;
                },

                vpisemoUporabnikaIzDropDownMenija: function () {

                    this.osebe.nove[this.osebe.nivo] =  [this.user.selected.split(',')];

                    // Prikažemo polje z uporabniki, ki so bili na novo dodani
                    this.prikaziUporabnike(this.osebe.nivo);

                    // Tekstovno polje spraznimo in ga skrijemo
                    this.user.selected = null;
                    this.osebe.prikazi = false;
                },

                vpisOsebNaNivoTextarea: function () {
                    var that = this;

                    // preverimo email in vrnemo napako, če obstaja
                    if (this.preveriPravilnostEmaila())
                        return this.email.opozorilo;

                    if (typeof this.user.selected !== 'undefined' && this.user.selected && this.user.selected.length > 0) {
                        var vpis = [this.user.selected];
                    } else {
                        // uporabnike razdelimo glede na \n in jih shranimo v polje
                        var vpis = this.osebe.textarea.split('\n');
                    }


                    this.osebe.nove.$set(that.osebe.nivo, []);
                    // ločimo še vejice
                    $.each(vpis, function (key, val) {
                        var loci = val.split(',');

                        // če je email večji od 4 znakov, ga shranimo kot novega drugače ne
                        if (loci[0].length > 4) {
                            that.osebe.nove[that.osebe.nivo].push(loci);
                        }
                    });

                    // Prikažemo polje z uporabniki, ki so bili na novo dodani
                    this.prikaziUporabnike(this.osebe.nivo);

                    // Tekstovno polje spraznimo in ga skrijemo
                    this.osebe.textarea = '';
                    this.osebe.prikazi = false;
                    this.user.selected = '';
                },

                // Preveri, če string ustreza pravilnemu zapis emaila
                preveriEmail: function (email) {
                    var EMAIL_REGEX = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

                    return EMAIL_REGEX.test(email);
                },

                // Preverimo pravilnost vpisanega emaila in vržemo napako
                preveriPravilnostEmaila: function () {
                    var that = this;

                    // uporabnike razdelimo glede na \n in jih shranimo v polje
                    var vpis = this.osebe.textarea.split('\n');

                    // vse napake postavimo na 0
                    this.email.napake = [];

                    // ločimo še vejice
                    $.each(vpis, function (key, val) {
                        var loci = val.split(',');

                        if (!that.preveriEmail(loci[0]) && loci[0].length > 0) {
                            that.email.napake.push({
                                naslov: loci[0],
                                vrstica: (key + 1)
                            });
                        }
                    });

                    // v kolikor so v poju zapisani napačni email naslovi potem prikažemo opozorilo
                    if (this.email.napake.length > 0)
                        return this.email.opozorilo = true;
                },

                // Preverimo, če uporabniki so že vpisani v bazi in jih prikažemo ali če so bili uporabniki na novo dodani
                prikaziUporabnike: function (level) {
                    // Uporabniki so bili na novo dodani na nivo
                    if (this.osebe.nove[level] && this.osebe.nove[level].length > 0)
                        return this.osebe.show.$set(level, true);

                    // imamo uporabni v SQL bazi
                    if (this.izbran.sifrantPodatki[level] && this.izbran.sifrantPodatki[level].uporabniki)
                        return this.osebe.show.$set(level, true);

                    return this.osebe.show.$set(level, false);
                },

                // Izbriši uporabnika iz this.osebe.nove
                izbrisiUporabnika: function (level) {
                    return this.osebe.nove.splice(level, 1);
                },

                // Izbriši uporabnika iz Sql baze, ker je že shranjen
                izbrisiUporabnikaIzBaze: function (userId, index, level) {
                    var that = this;

                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=brisi&m=uporabnika', {
                        uporabnik_id: userId,
                        struktura_id: this.izbran.sifrantPodatki[level].id
                    }).then(function () {
                        that.izbran.sifrantPodatki[level].uporabniki.splice(index, 1);
                    });

                },

                // Preverimo v SQL-u, da dobimo za vpisane šifrante ID in parent_id
                // Rekurzivna funkcija, ki ob sumbitu preveri v bazi in vsem še obstoječim šifrantom doda id in parent_id
                preveriBazoZaSifrant: function (parent_id, key) {
                    var that = this;

                    // Polje z omenjenim elementom mora obstajati, drugače smo prišli do konca
                    if (this.izbran.sifrantPodatki[key]) {

                        // V kolikor element že ima parent id, potem tega elementa ne preverjamo in gremo preverit naslednji element
                        // Prvi element vedno preverimo (key == 1)
                        if (key > 1 && this.izbran.sifrantPodatki[key] && this.izbran.sifrantPodatki[key].parent_id != 'null') {
                            var st = key + 1;
                            this.preveriBazoZaSifrant(this.izbran.sifrantPodatki[key].id, st);
                        }

                        // AJAX request, da preveri podatke o elementu in pridobi parent_id
                        this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=preveri-sifrant-za-nivo', {
                            level: this.izbran.sifrantPodatki[key].level,
                            hierarhija_sifranti_id: this.izbran.sifrantPodatki[key].hierarhija_sifranti_id,
                            parent_id: parent_id
                        }).then(function (i) {

                            // shranimo na ključ, kjer je nivo celo polje
                            that.izbran.sifrantPodatki.$set(i.data.level, i.data);

                            // V kolikor vsebuje podatke o uporabnikih potem te rudi prikaže
                            that.prikaziUporabnike(i.data.level);

                            // Pridobimo številko naslednjega elementa
                            var st = 1 + Number(i.data.level);

                            // Pokličemo rekurzivno funkcijo, da kjer je paren_id, id trenutnega elementa
                            that.preveriBazoZaSifrant(i.data.id, st);
                        });
                    }

                    return 0;
                },

                // pridobimo vse nastavitve iz baze
                pridobiNastavitveCeJeVpisanaStruktura: function () {
                    var that = this;

                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=hierarhija-options&m=get').success(function (data) {

                        if (data.length == 0)
                            return that.vpisanaStruktura = false;

                        $.each(data, function (index, value) {
                            if (index == 'vpisana_struktura')
                                that.vpisanaStruktura = value;
                        });
                    });
                },

                // Preveri, če obstaja med opcijami vpisana_struktura, drugače jo vnese
                aliJeStrukturaVnesena: function () {
                    if (this.vpisanaStruktura)
                        return this.vpisanaStruktura;

                    // V kolikor gre za vpis v bazo
                    hierarhijaApp.getSaveOptions('vpisana_struktura', 1);
                    this.vpisanaStruktura = 1;
                },

                // Preverimo, je izbran element za sledeči nivo, če je nivo večje kot zadnje nivo in če na zadnjem nivoju še ni vpisanega uporabnika potem dovoli prikaz ikone za vnos uporabnikov
                aliPrikazemIkonoZaDodajanjeUporabnikov: function (level) {
                    var level = level || false;

                    if (!level)
                        return false;

                    if (this.izbran.sifrant[level] > 0 &&
                        (level < this.podatki.maxLevel ||
                        level == this.podatki.maxLevel &&
                        this.izbran.sifrantPodatki[level] &&
                        !this.izbran.sifrantPodatki[level].hasOwnProperty('uporabniki'))
                    )
                        return true;

                    return false;
                },

                /*
                 * Pridobimo vse ID-je že vpisanih šifrantov skupaj z uporabniki
                 * izhajamo pa iz zadnjega ID-ja
                 */
                pridobiIdSifrantovInUporabnike: function (idLast) {
                    var that = this;

                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=kopiranje-vrstice', {
                        id: idLast
                    }).then(function (response) {
                        // response ok in imamo objekt
                        if (response.status == 200 && response.data.length > 0) {
                            response.data.forEach(function (val) {
                                that.izbran.sifrantPodatki.$set(val.izbrani.level, val.izbrani);
                                $('option[value="' + val.izbrani.hierarhija_sifranti_id + '"]').parent().val(val.izbrani.hierarhija_sifranti_id).trigger('change');
                            });

                            $(window).scrollTop(0);
                        }
                    });

                },

                /**
                 * Shranimo celotno strukturo z uporabniki v srv_hierarhija_shrani
                 */
                shraniUporabnikeNaHierarhijo: function () {

                    this.$http.post('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=shrani-strukturo-hierarhije', {
                        id: this.anketaId,
                        shrani: 1
                    });
                },

                /**
                 * Pridobimo uporabnike, ki jih imamo shranjene v bazi za drop down list
                 */
                pridobiUporabnikeZaDropdownList: function () {
                    var that = this;

                    this.$http.get('ajax.php?anketa=' + this.anketaId + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=import-user&s=getAll').success(function (data) {
                        that.user.dropdown = data;
                    });
                },


                /**************** funkcije, ki preveri true/false **************/
                preveriCejeEmailZeVnesenVbazoZaUcitelja: function (maxLevel) {
                    var maxLevel = maxLevel || 0;

                    if(maxLevel === 0 || this.izbran.sifrantPodatki[maxLevel] !== null)
                        return false;

                    if(this.izbran.sifrantPodatki[maxLevel] !== null && this.izbran.sifrantPodatki[maxLevel].uporabniki.length > 0)
                        return true;

                    return false;
                },

                prikaziJsKoSeJeCelaSpletnaStranZeNalozila: function(level){
                    var level = level || 0;

                    if((level == 1 && this.pageLoadComplete) || (this.izbran.sifrant[level-1] > 0 && this.izbran.sifrant[level-1].length > 0))
                        return true;

                    return false;
                },

                prikaziSelectZaZadnjiNivo: function(level) {
                    var level = level || 0;
                    this.osebe.nivo = level;

                    var prikazi =  this.aliPrikazemIkonoZaDodajanjeUporabnikov(level);

                    if(level === this.podatki.maxLevel && this.user.dropdown && prikazi)
                        return true;

                    return false;
                },

            },
        });
    }

    if (document.querySelector('#vue-custom')) {
        new Vue({
            el: '#vue-custom',
            data: {
                anketaId: $('#srv_meta_anketa_id').val(),
                managerOznaciVse: true,
                statusTabela: '',
                supersifra: [],
            },
            methods: {
                managerZamenjajOznaci: function () {
                    return this.managerOznaciVse = !this.managerOznaciVse;
                },
                emailObvestiloZaManagerje: function () {
                    event.preventDefault();

                    var polje = [];
                    $('[name="manager"]:checked').each(function () {
                        polje.push($(this).val());
                    });

                    //Poljšemo podatke
                    $.post("ajax.php?anketa=" + this.anketaId + "&t=hierarhija-ajax&a=ostalo&m=obvesti-managerje", {
                        managerji: polje
                    }).then(function (response) {
                        $('[name="manager"]:checked').each(function () {
                            $(this).hide();
                            $(this).parent().prepend('<span> - </span>');
                        });

                        if (response.data == 'success') {
                            swal({
                                title: "Obvestilo poslano!",
                                text: "Elektronsko sporočilo je bilo uspešno poslano.",
                                type: "success",
                                timer: 3000
                            });
                        }

                    });

                }
            }
        });
    }
});

function izbrisiSifrant(id) {
    var anketa_id = $('#srv_meta_anketa_id').val();
    $.post("ajax.php?anketa=" + anketa_id + "&t=hierarhija-ajax&a=izbrisi_sifrant", {
        idSifranta: id
    }).then(function (response) {
        if (response == 1)
            return swal({
                title: "Opozorilo!",
                text: "Šifrant je že uporabljen in ga ni mogoče izbrisati.",
                type: "error",
                timer: 3000
            });

        $('[data-sifrant="' + id + '"]').remove();
    });
}

// Pobriše vrstico iz DataTables in odstrani šifrante iz vseh nivojev pri izbiri
function pobrisiVrsticoHierarhije(id) {
    gradnjaHierarhijeApp.$set('izbran.sifrant', []);
    gradnjaHierarhijeApp.$set('izbran.sifrantPodatki', []);

    // V kolikor gre za uporabnika na nižjem nivoju potem moramo ponovno pridobiti strukturo in vse podatke o fiksnih nivojih
    gradnjaHierarhijeApp.preveriNivoInPraviceUporabnika();

    brisiVrsticoHierarhije(id, 1);
}