//===============================================================================

const PK_FORM_TYPE_FILE = 1;
const PK_FORM_TYPE_KM = 2;
const PK_FORM_TYPE_KSP = 3;
const MAX_FILE_SIZE = 25 * 1024 * 1024;

//===============================================================================
var JS_WORKER_PATH = "/iit-v2/js/euscp.worker.ex.js";

// Налаштування бібліотеки
var euSettings = {
    language: "uk",
    encoding: "utf-8",
    httpProxyServiceURL: "/iit-v2/server/ProxyHandler.php",
    directAccess: true,
    CAs: "/iit-v2/Data/CAs.json",
    CACertificates: "/iit-v2/Data/CACertificates.p7b",
    allowedKeyMediaTypes: [
        "е.ключ ІІТ Алмаз-1К",
        "е.ключ ІІТ Кристал-1",
        "ID-карта громадянина (БЕН)",
        "е.ключ ІІТ Алмаз-1К (PKCS#11)",
        "е.ключ ІІТ Кристал-1 (PKCS#11)",
    ],

    // Реєстрація хмарних провайдерів
    KSPs: [
        // {
        //     name: "ДіЯ",
        //     ksp: 7,
        //     address: "Server/KSPSignController.php",
        //     port: "",
        //     directAccess: true,
        //     mobileAppName: "ДіЯ",
        //     codeEDRPOU: "43395033",
        //     signAlgos: [1, 3],
        // },
        {
            name: "DepositSign - хмарний підпис",
            ksp: 4,
            address: "https://depositsign.com/api/v1/informjust/sign-server",
            port: "",
            directAccess: true,
            codeEDRPOU: "43005049",
        },
        {
            name: 'Приватбанк - хмарний підпис "SmartID"',
            ksp: 6,
            address: "https://acsk.privatbank.ua/cloud/api/back/",
            port: "",
            directAccess: true,
            clientIdPrefix: "IEIS_",
            confirmationURL: "https://www.privat24.ua/rd/kep",
            mobileAppName: "Приват24",
            codeEDRPOU: "14360570",
        },
        // {
        //     name: "Вчасно - хмарний підпис (QR)",
        //     ksp: 6,
        //     address: "https://cs.vchasno.ua/ss/",
        //     port: "",
        //     directAccess: true,
        //     clientIdPrefix: "vchasno_",
        //     confirmationURL: "https://cs.vchasno.ua/rd/",
        //     mobileAppName: "Вчасно.КЕП",
        //     codeEDRPOU: "41231992",
        // },
        {
            name: "ТОВ «ЦСК «Україна» - хмарний підпис CloudKey",
            ksp: 6,
            address: "https://sid.uakey.com.ua/smartid/iit/",
            port: "",
            directAccess: true,
            clientIdPrefix: "DIIA_2",
            confirmationURL: "https://sid.uakey.com.ua/kep?hash=rd/kep",
            mobileAppName: "CloudKey",
            codeEDRPOU: "36865753",
        },
        {
            name: "ESign - хмарний підпис",
            ksp: 4,
            address: "https://cabinet.e-life.com.ua/api/EDG/Sign",
            port: "",
            directAccess: true,
            codeEDRPOU: "36049014",
        },
        {
            name: "ПУМБ - хмарний підпис",
            ksp: 6,
            address: "https://apiext.pumb.ua/hogsmeade/striga/v1",
            port: "",
            directAccess: false,
            clientIdPrefix: "DIIA_3",
            confirmationURL: "https://www.pumb.ua/qes",
            mobileAppName: "",
            codeEDRPOU: "14282829",
        },
        {
            name: "ДПС - хмарний підпис",
            ksp: 4,
            address: "https://smart-sign.tax.gov.ua/",
            port: "443",
            directAccess: true,
            clientIdType: 1,
            codeEDRPOU: "43174711",
        },
        {
            name: "Укргазбанк - хмарний підпис «EcoSign»",
            ksp: 6,
            address: "https://vtms-api-qca.ukrgasbank.com/iit-signer/api/v1",
            port: "",
            directAccess: false,
            clientIdPrefix: "UGB_PROD",
            confirmationURL: "https://cihsm-commiter-qca.ukrgasbank.com",
            mobileAppName: "",
            codeEDRPOU: "23697280",
        },
        {
            name: "Банк Альянс - хмарний підпис",
            ksp: 6,
            address: "https://cihsm-api.bankalliance.ua/iit-signer/api/v1",
            port: "",
            directAccess: false,
            clientIdPrefix: "ALLIANCE_PROD",
            confirmationURL: "https://cihsm-commiter.bankalliance.ua",
            mobileAppName: "",
            codeEDRPOU: "14360506",
        },
        {
            name: "КНЕДП органів прокуратури - хмарний підпис",
            ksp: 6,
            address: "https://cihsm-api.gp.gov.ua/iit-signer/api/v1",
            port: "",
            directAccess: false,
            clientIdPrefix: "OGP_PROD",
            confirmationURL: "https://cihsm-commiter.gp.gov.ua",
            mobileAppName: "",
            codeEDRPOU: "00034051",
        },
        {
            name: "АМО ФІНТЕХ – хмарний підпис AMOKEY",
            ksp: 6,
            address: "https://sserver.amokey.com",
            port: "443",
            clientIdPrefix: "",
            directAccess: true,
            clientIdType: 1,
            codeEDRPOU: "44669502",
        },
        {
            name: "Ощадбанк - хмарний підпис",
            ksp: 6,
            address: "https://cihsm-api.oschadbank.ua/iit-signer/api/v1",
            port: "",
            directAccess: false,
            clientIdPrefix: "OSCHADBANK_MOBILE_PROD",
            confirmationURL: "https://smartid.oschadbank.ua/widgetidua",
            mobileAppName: "BC.C SmartID",
            codeEDRPOU: "00032129",
        },
        {
            name: 'Сервер підпису КНЕДП "Військова частина 2428" ДПСУ',
            ksp: 6,
            address: "https://smart-sign.dpsu.gov.ua/",
            port: "443",
            clientIdPrefix: "",
            clientIdType: 1,
            directAccess: true,
            codeEDRPOU: "14321469",
        },
    ],

    //   KSPs: [
    //     {
    //       name: "ІІТ - хмарний підпис (2)",
    //       ksp: EndUserConstants.EndUserKSP.IIT,
    //       address: "https://sserver2.iit.com.ua",
    //       port: "443",
    //     },
    //   ],
};

// Бібліотека для роботи з файловими ключами та серверами підпису, що не потребує
// встановлення додатково ПЗ
// var euSignFile = new EndUser(
//     "iit_v2\\js\\euscp.worker.ex.js",
//     EndUserConstants.EndUserLibraryType.JS
// );

// Бібліотека для роботи з аппаратними носіями, що потребує
// встановлення додатково ПЗ бібліотек веб-підпису, веб-розширення для браузера
// var euSignKeyMedia = new EndUser(null, EndUserConstants.EndUserLibraryType.SW);
// var keyMedias = [];

// var euSign = euSignFile;
var formType = PK_FORM_TYPE_FILE;

/**
 * Цей клас являє собою об'єкт, який містить деталі про криптографічний ключ,
 * отриманий від Key Storage Provider. Він зберігає таку інформацію, як ідентифікатор провайдера ключа,
 * ім'я провайдера, ідентифікатор користувача, ідентифікатор ключа та інформацію про емітента.
 */
class KspKeyDetails {
    /**
     * @param {number} ksp - Тип криптографічного провайдера.
     * @param {string} kspName - Зрозуміла назва провайдера.
     * @param {string} userId - Ідентифікатор користувача.
     * @param {string} keyId - Унікальний ідентифікатор ключа.
     * @param {string} issuerCN - Загальне ім'я видавця сертифіката.
     */
    constructor(ksp, kspName, userId, keyId, issuerCN) {
        this.ksp = ksp;
        this.kspName = kspName;
        this.userId = userId;
        this.keyId = keyId;
        this.issuerCN = issuerCN;
    }

    /**
     * Повертає зрозумілий ідентифікатор провайдера.
     * Для відомих провайдерів повертає ім'я, для інших — ідентифікатор.
     * @returns {string} Ідентифікатор KSP.
     */
    GetKSPId() {
        const knownKSPs = [
            EndUserConstants.EndUserKSP.IIT,
            EndUserConstants.EndUserKSP.PB,
            EndUserConstants.EndUserKSP.DIIA
        ];

        // Якщо тип KSP є одним із відомих, повертаємо його ім'я.
        if (knownKSPs.includes(this.ksp)) {
            return this.kspName;
        }

        // В іншому випадку повертаємо сам ідентифікатор.
        return this.ksp;
    }
}

/**
 * Конструктор FileWrapper створює об'єкт, що представляє файл.
 * Він обгортає оригінальний об'єкт File і його дані.
 */
class FileWrapper {
    constructor(fileName, fileData, originalFile = null) {
        this.name = fileName;
        this.data = fileData;
        this.file = originalFile;
        // Розмір файлу обчислюється на основі наданих даних або оригінального файлу.
        this.size = fileData ? fileData.length : originalFile ? originalFile.size : 0;
    }
}

/**
 * Цей клас є менеджером для роботи з бібліотекою EndUser.
 * Він відповідає за ініціалізацію, відстеження її стану завантаження, перевірку сумісності,
 * реєстрацію обробників подій та асинхронне завантаження інформації про бібліотеку.
 */
class LibraryManager {
    // Конструктор класу, який ініціалізує властивості.
    constructor(type) {
        this.m_type = type;
        this.m_library = new EndUser(JS_WORKER_PATH, type);
        this.m_info = null;
        this.m_loading = false;
        this.m_eventsRegistered = false;
    }

    // Методи доступу (геттери)
    GetType() {
        return this.m_type;
    }

    IsSupported() {
        return this.m_info && this.m_info.supported;
    }

    IsLoaded() {
        return this.m_info && this.m_info.loaded;
    }

    IsLoading() {
        return this.m_loading;
    }

    isEventsRegistered() {
        return this.m_eventsRegistered;
    }

    // Методи-сеттери
    SetEventsRegistered() {
        this.m_eventsRegistered = true;
    }

    // Методи для отримання об'єктів
    GetLibrary() {
        return this.m_library;
    }

    GetInfo() {
        return this.m_info;
    }

    // Асинхронний метод для завантаження бібліотеки
    async Load(eventHandler) {
        this.m_loading = true;

        try {
            await this.m_library.AddEventListener(
                EndUserConstants.EndUserEventType.All,
                eventHandler
            );
            const libraryInfo = await this.m_library.GetLibraryInfo();
            this.m_info = libraryInfo;
            this.m_loading = false;
        } catch (error) {
            this.m_loading = false;
            throw error;
        }
    }
}

/**
 * Цей клас є центральним об'єктом, що містить повну інформацію про криптографічний ключ.
 * Він абстрагує деталі зберігання ключа (наприклад, у файлі, на фізичному носії, в хмарному сховищі)
 * і надає методи для стандартизованого доступу до його властивостей, таких як тип та емітент.
 */
class KeyInfo {
    constructor() {
        this.keyMedia = null;
        this.file = null;
        this.alias = null;
        this.password = null;
        this.certificates = null;
        this.kspKey = null;
    }

    getKeyType() {
        return null != this.file
            ? KeyStoreType.FileKey
            : null != this.keyMedia
                ? KeyStoreType.KeyMedia
                : null != this.kspKey
                    ? this.kspKey.ksp == i.EndUserConstants.EndUserKSP.DIIA
                        ? KeyStoreType.Diia
                        : KeyStoreType.CloudKey
                    : KeyStoreType.Unknown;
    }

    getIssuerCN() {
        return this.certificatesInfo && this.certificatesInfo.length > 0
            ? this.certificatesInfo[0].infoEx.issuerCN
            : null != this.kspKey
                ? this.kspKey.issuerCN
                : null;
    }
}

var StringFormatter = (function () {

    // Приватний конструктор, не використовується
    function StringFormatter() {
    }

    /**
     * Метод для форматування рядків.
     *
     * @param {string} templateString - Рядок-шаблон, що містить заповнювачі, наприклад "Привіт, {0}!".
     * @param {...any} values - Значення, якими потрібно замінити заповнювачі.
     * @returns {string} Відформатований рядок.
     */
    StringFormatter.format = function (templateString) {
        // Зберігаємо всі аргументи, окрім першого (templateString)
        var args = Array.prototype.slice.call(arguments, 1);

        // Використовуємо регулярний вираз для пошуку заповнювачів типу {0}, {1}
        return templateString.replace(/{(\d+)}/g, function (match, index) {
            // Перевіряємо, чи існує аргумент з відповідним індексом
            if (typeof args[index] !== 'undefined') {
                return args[index];
            }

            // Якщо аргумента не існує, залишаємо заповнювач без змін
            return match;
        });
    };

    return StringFormatter;
})();

class App {
    /**
     * Містить константи, що вказують на різні типи сховищ для ключів: файлове сховище (FileKey),
     * фізичний носій (KeyMedia), хмарне сховище (CloudKey) та спеціалізоване сховище "Дія" (Diia).
     */
    static KeyStoreType = {
        File: 1,
        Hardware: 2,
        KSP: 4,
        DIIA_UA: 5,
        DIIA_EU: 6,
    };

    /**
     * Це перелічення містить константи, які відповідають різним стандартам та форматам цифрового підпису,
     * як-от XAdES, PAdES та CAdES.
     */
    static SignatureFormat = {
        XAdES: 1,
        PAdES: 2,
        CAdES: 3,
        ASiCS: 4,
        ASiCE: 5,
    };


    constructor() {

        this.formType = PK_FORM_TYPE_FILE
        //Створюємо масив для доступних бібліотек
        this.libraries = new Array();
        //Додаємо бібліотеки
        this.libraries[EndUserConstants.EndUserLibraryType.JS] = new LibraryManager(EndUserConstants.EndUserLibraryType.JS);
        this.libraries[EndUserConstants.EU_LIBRARY_TYPE_SW] = new LibraryManager(EndUserConstants.EndUserLibraryType.SW);
        this.BindEvents();
        this.OnChangeLibraryType();
        this.SetKSPs();

    }

    SetLibraryType(type) {

        var pkFileBlock = document.getElementById("pkFileBlock");
        // var pkKeyMediaBlock = document.getElementById("pkKeyMediaBlock");
        var pkKSPBlock = document.getElementById("pkKSPBlock");
        // var signBlock = document.getElementById("signBlock");

        this.formType = type;

        switch (type) {
            case PK_FORM_TYPE_FILE:
                pkFileBlock.style.display = "block";
                // pkKeyMediaBlock.style.display = "none";
                pkKSPBlock.style.display = "none";
                // signBlock.style.display = "none";
                break;

            case PK_FORM_TYPE_KM:
                pkFileBlock.style.display = "none";
                // pkKeyMediaBlock.style.display = "block";
                pkKSPBlock.style.display = "none";
                // signBlock.style.display = "none";
                break;

            case PK_FORM_TYPE_KSP:
                pkFileBlock.style.display = "none";
                // pkKeyMediaBlock.style.display = "none";
                pkKSPBlock.style.display = "block";
                // signBlock.style.display = "none";
                break;
        }
        this.OnChangeLibraryType();

    }

    /*
      Обробник сповіщень на підтвердження операції з використання ос. ключа
      за допомогою сканування QR-коду в мобільному додатку сервісу підпису
      */
    onConfirmKSPOperation(kspEvent) {
        let t = this;
        setStatus('Формування QR-code', 1);
        try {

            switch (kspEvent.type) {
                case EndUserConstants.EndUserEventType.ConfirmKSPOperation: {
                    t.BeginOperationConfirmation(
                        kspEvent.url,
                        kspEvent.qrCode,
                        kspEvent.mobileAppName,
                        kspEvent.expireDate
                    );
                }
            }
            // (this.m_listeners[kspEvent.type] ||
            //     this.m_listeners[EndUserConstants.EndUserEventType.All]) &&
            // this.PostMessage(null, -2, null, kspEvent);
        } catch (e) {
            setStatus(e, 1, 1);
        }

        // console.log(kspEvent);
        // var node = "";
        // node += '<a href="' + encodeURI(kspEvent.url) + '" target="_blank">';
        // node +=
        //     '<img src="data:image/bmp;base64,' +
        //     kspEvent.qrCode +
        //     '" style="padding: 10px; background: white;">';
        // node += "</a>";
        //
        // document.getElementById("pkKSPQRImageBlock").innerHTML = node;
        // document.getElementById("pkKSPQRBlock").style.display = "block";
    }

    StopOperationConfirmationTimer() {
        setStatus('');
        void 0 !== this.m_dimmerViewTimer &&
        (clearInterval(this.m_dimmerViewTimer),
            (this.m_dimmerViewTimer = void 0));
        $("#pkKSPQRTimerLabel").text("");
        $("#pkKSPQRTimerBlock").hide();
        $("#pkKSPQRBlock").hide();
    }

    BeginOperationConfirmationTimer(expireDate, msg, callback) {
        var t = this;
        let i = function () {
            let i = expireDate.getTime() - new Date().getTime();
            let o = Math.floor((i / 1e3) % 60);
            let s = Math.floor((i / 1e3 / 60) % 60);
            let a = msg + " " + ("0" + s).slice(-2) + ":" + ("0" + o).slice(-2);
            $("#pkKSPQRTimerLabel").text(a);
            i <= 0 &&
            (clearInterval(t.m_dimmerViewTimer),
                (t.m_dimmerViewTimer = void 0),
                callback());
        };
        i();
        t.m_dimmerViewTimer = setInterval(i, 1e3);
        $("#pkKSPQRTimerBlock").show();
    }

    StopOperationConfirmation() {
        this.StopOperationConfirmationTimer();
        $("#pkKSPQRBlock").empty();
        $("#pkKSPQRBlock").hide();
    }

    BeginOperationConfirmation(url, qrCode, mobileAppName, expireDate) {
        let t = this;
        t.NextStage('fn_iit_module_init_key_stage', 'fn_iit_module_init_qr_code');
        let $div = $("<div>");
        $div.css("padding", "10px");

        var $a = $("<a>");
        $a.attr("href", url), $a.attr("target", "_blank");

        var image = new Image();
        image.src = "data:image/bmp;base64," + qrCode;
        $(image).css("padding", "10px");
        $(image).css("background", "white");
        $a.append(image);
        $div.append($a);

        var c = '<a href="' + encodeURI(url) + '" target="blank" style="color:black">' + mobileAppName + "</a>";

        let $div1 = $("<div>");
        $div1.append(
            '<label style="color:#aaa">' +
            StringFormatter.format('Натисність або зчитайте QR-код сканером у застосунку {0} та дотримуйтесь інструкцій', c) +
            "</label>"
        );

        t.BeginOperationConfirmationTimer(
            expireDate,
            'QR-код буде дійсним ще',
            function () {
                t.StopOperationConfirmationTimer();
            }
        );

        $("#pkKSPQRBlock").empty();
        $("#pkKSPQRBlock").append($div);
        $("#pkKSPQRBlock").append($div1);
        $("#pkKSPQRBlock").show();
    }

    LoadLibrary(e) {
        let t = this;
        let currentLibrary = t.GetCurrentLibrary();
        currentLibrary.IsLoading() ||
        currentLibrary
            .Load(function (e) {
                t.onConfirmKSPOperation(e);
            })
            .then(function () {
                currentLibrary == t.GetCurrentLibrary() && t.OnChangeLibraryType(e);
            })
            .catch(function (e) {
                currentLibrary == t.GetCurrentLibrary();
                setStatus(e, 0, 1);
                // t.SetError(p(o.ERROR_LIBRARY_LOAD), e);
                // t.CloseDimmerView();
            });
    }

    OnChangeLibraryType(e) {
        let t = this;
        var currentLibrary = this.GetCurrentLibrary();
        if (null == currentLibrary.GetInfo()) {
            // t.ShowDimmerView(p(o.PROCESS_STATUS_LOAD_LIBRARY));
            return void this.LoadLibrary(e);
        }

        if (!currentLibrary.IsSupported()) {
            return void t.SetError(p(o.ERROR_LIBRARY_NOT_SUPPORTED));
        }

        if (!currentLibrary.IsLoaded()) {

        }

        var object = {isInitialized: !1};

        currentLibrary.GetLibrary()
            .IsInitialized()
            .then(function (e) {
                return (
                    (object.isInitialized = e),
                        object.isInitialized
                            ? null
                            : (setStatus('Ініціалізація криптографічної бібліотеки', 0),
                                currentLibrary.GetLibrary().Initialize(euSettings))
                );
            })
            .then(function () {
                return object.isInitialized
                    ? null
                    : currentLibrary.GetLibrary().SetRuntimeParameter("StringEncoding", 65001);
            })
            .then(function () {
                return currentLibrary.GetLibrary().GetCAs();
            })
            .then(function (n) {
                t.SetCAs(n);
                setStatus('Бібліотеку завантажено', true);
            })
            .catch(function (e) {
                setStatus('Виникла помилка при ініціалізації криптографічної бібліотеки\n' + e, 0, 1);
            });

    }


    GetSelectedKSPSettigs() {
        var e = $("#pkKSPSelect").find(":selected").text();
        let t = euSettings.KSPs || [];
        for (let n = 0; n < t.length; n++) {
            if (t[n].name == e) {
                return t[n];
            }
        }
        return null;
    }

    MakeUserId() {
        return "undefined" != typeof crypto
            ? ("" + [1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(
                /[018]/g,
                function (e) {
                    var t = Number(e);
                    return (
                        t ^
                        (crypto.getRandomValues(new Uint8Array(1))[0] &
                            (15 >> (t / 4)))
                    ).toString(16);
                }
            )
            : "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(
                /[xy]/g,
                function (e) {
                    var t = (16 * Math.random()) | 0;
                    return ("x" == e ? t : (3 & t) | 8).toString(16);
                }
            );
    }

    OnReadKSPSelectChange() {

        let t = this;
        let e = t.GetSelectedKSPSettigs();
        let val = parseInt($("#pkKSPSelect").find(":selected").val());


        let n =
            val != EndUserConstants.EndUserKSP.IIT ||
            (void 0 !== e.clientIdType &&
                e.clientIdType !=
                EndUserConstants.EndUserKSPClientIdType.Default)
                ? "none"
                : "uppercase";

        $("#pkKSPUserId").css("text-transform", n);

        var r = !0;
        switch (val) {
            case EndUserConstants.EndUserKSP.PB:
                r = !e.confirmationURL;
                break;
            case EndUserConstants.EndUserKSP.DIIA:
                r = !1;
                break;
            default:
                r = !0;
        }

        if (r) {
            $("#pkKSPUserIdBlock").show()
        } else {
            $("#pkKSPUserIdBlock").hide()
        }

        $("#pkKSPUserId").val(r ? "" : t.MakeUserId());
        $("#pkKSPUserId").change();
        // this.OnReadKSPUserIdTextFieldChange();
    }

    getSelectedKSP() {
        var kspSelected = document.getElementById("pkKSPSelect").value;

        for (var i = 0; i < euSettings.KSPs.length; i++) {
            if (euSettings.KSPs[i].name == kspSelected) return euSettings.KSPs[i];
        }

        return null;
    }

    GetSelectedCA() {
        if (null == this.m_CAs || 0 == this.m_CAs.length) return null;
        var e = parseInt($("#pkCASelect").val());
        return 0 != e ? this.m_CAs[e - 1] : null;
    }

    OnResetPKey() {
        let t = this;
        let currentLibrary = t.GetCurrentLibrary();
        t.m_readedPKey = null;

        currentLibrary
            .GetLibrary()
            .ResetPrivateKey()
            .then(function () {
                "#pkTypeDIIAUAMenuItem" == t.GetPreSelectMenuId() ||
                "#pkTypeDIIAEUMenuItem" == t.GetPreSelectMenuId()
                    ? t.OnReadPKeyCancel()
                    : t.OnChangeLibraryType();
            })
            .catch(function () {
                "#pkTypeDIIAUAMenuItem" == t.GetPreSelectMenuId() ||
                "#pkTypeDIIAEUMenuItem" == t.GetPreSelectMenuId()
                    ? t.OnReadPKeyCancel()
                    : t.OnChangeLibraryType();
            });
    }

    OnReadPKey(isTrue) {
        setStatus('Зчитування особистого ключа');
        var t = this;
        var currentLibrary = t.GetCurrentLibrary();

        if (t.m_isPKActionDone || null != t.m_readedPKey) {
            t.OnResetPKey();
        } else {
            var readFileAliasSelect = isTrue ? $("#pkReadFileAliasSelect") : null;

            //Password
            // var passInput = isTrue
            //     ? $("#pkReadFilePasswordTextField")
            //     : $("#pkReadKMPasswordTextField");
            var passInput = $("#pkReadFilePasswordTextField");

            var readKMUserTextField = isTrue ? null : $("#pkReadKMUserTextField");

            var l = isTrue
                ? $("#pkReadFileCertsInput")
                : $("#pkReadKMCertsInput");

            var c = isTrue
                ? $("#pkReadFileCertsBlock")
                : $("#pkReadKMCertsBlock");

            var selectedKM = isTrue ? null : t.GetSelectedKM();

            var file = isTrue ? $("#pkReadFileInput").prop("files")[0] : null;

            if (typeof file == 'undefined' || !file) {
                setStatus('Файл з особистим ключем не обрано', 1, 1);
                t.showStageErrors('fn_iit_module_init_key_stage', 1, 'Файл з особистим ключем не обрано');
                return;
            }


            var E = isTrue && "jks" == t.GetFileExtension(file.name);
            var keyName = E ? readFileAliasSelect.val() : null;

            var S = new Array();

            var password = passInput.val();

            var selectedCA = t.GetSelectedCA();

            var currentCA = null != selectedCA ? selectedCA.issuerCNs[0] : null;


            // if (readKMUserTextField && readKMUserTextField.is(":visible") && "" == readKMUserTextField.val()) {
            //     readKMUserTextField.focus();
            //     return void t.SetError(p(o.ERROR_USER_NOT_SET));
            // }

            if (typeof password == 'undefined' || "" == password) {
                setStatus('Не вказано пароль до особистого ключа', 1, 1);
                t.showStageErrors('fn_iit_module_init_key_stage', 1, 'Не вказано пароль до особистого ключа');
                return;
            }

            if (c.is(":visible") && 0 == (S = l.prop("files")).length)
                t.SetError(p(o.ERROR_CERTIFICATES_NOT_SELECTED));
            else {
                var keyInfo = new KeyInfo();
                keyInfo.keyMedia = selectedKM;
                keyInfo.alias = keyName;
                keyInfo.password = password;
                t.ReadFiles(S)
                    .then(function (e) {
                        var n = Array();
                        if (e) for (var r = 0; r < e.length; r++) n.push(e[r].data);
                        return (keyInfo.certificates = n), file ? t.ReadFile(file) : null;
                    })
                    .then(function (e) {
                        return (
                            (keyInfo.file = e),
                                E ? currentLibrary.GetLibrary().GetJKSPrivateKeys(e.data) : null
                        );
                    })
                    .then(function (r) {
                        var i = isTrue ? keyInfo.file.data : null;
                        if (E && isTrue && 0 != r.length)
                            for (var o = 0; o < r.length; o++)
                                if (r[o].alias == keyName) {
                                    i = r[o].privateKey;
                                    for (
                                        var s = t.FilterUserCertificates(r[o].certificates),
                                            a = 0;
                                        a < s.length;
                                        a++
                                    )
                                        keyInfo.certificates.push(s[a].data);
                                    break;
                                }
                        return isTrue
                            ? currentLibrary
                                .GetLibrary()
                                .ReadPrivateKeyBinary(
                                    i,
                                    keyInfo.password,
                                    keyInfo.certificates,
                                    currentCA
                                )
                            : currentLibrary
                                .GetLibrary()
                                .ReadPrivateKey(keyInfo.keyMedia, keyInfo.certificates, currentCA);
                    })
                    .then(function (e) {
                        t.SetSelectedCA(e.issuerCN);

                        return currentLibrary.GetLibrary().GetOwnCertificates();
                    })
                    .then(function (e) {
                        t.IsQualifiedCertificates(e)
                            ? (
                                (keyInfo.certificatesInfo = e),
                                    t.SetViewPKeyInfo(keyInfo)
                            )
                            : currentLibrary
                                .GetLibrary()
                                .ResetPrivateKey()
                                .then(function () {
                                    t.BeginUpdateKMs(), t.CloseDimmerView();
                                    var n = s.format(
                                        p(o.ERROR_USE_ADVANCED_CERTS_UNSUPPORTED),
                                        e[0].infoEx.issuerCN
                                    );
                                    t.SetError(n);
                                })
                                .catch(function (e) {
                                    throw e;
                                });

                        /*Показуємо Етап 2 Інформація по ключу*/
                        t.NextStage('fn_iit_module_init_key_stage', 'fn_iit_module_data_verification_stage');
                        setStatus('Зчитування ключа успішно', true);
                    })
                    .catch(function (e) {
                        var error = 'Виникла помилка при зчитуванні особистого ключа\n';
                        setStatus(error + e, 1, 1);
                        t.showStageErrors('fn_iit_module_init_key_stage', 1, error);
                        if (e.code == EndUserError.EU_ERROR_CERT_NOT_FOUND) {
                            var a = isTrue
                                ? "#pkReadFileCertsBlock"
                                : "#pkReadKMCertsBlock";

                            t.m_formType == KeyOperations.MakeNewCertificate
                                ? ((error = p(o.ERROR_MAKE_NEW_CERTIFICATE)),
                                    (e = s.format(
                                        p(o.ERROR_MAKE_NEW_CERTIFICATE_INVALID_CA),
                                        currentCA
                                    )))
                                : ((e =
                                    null == selectedCA
                                        ? p(o.ERROR_READ_PRIVATE_KEY_CA_AUTO_DETECT)
                                        : s.format(
                                        selectedCA.cmpAddress
                                            ? p(o.ERROR_READ_PRIVATE_KEY_INVALID_CA)
                                            : p(o.ERROR_READ_PRIVATE_NEED_CERTIFICATE),
                                        currentCA
                                        )),
                                null == selectedCA || selectedCA.cmpAddress || $(a).show());
                        }
                    });
            }
        }
    }


    GetCurrentLibrary() {
        return this.libraries[
            this.formType == App.KeyStoreType.Hardware
                ? EndUserConstants.EndUserLibraryType.SW
                : EndUserConstants.EndUserLibraryType.JS
            ];
    }

    SetKSPs() {
        var kspSelect = document.getElementById("pkKSPSelect");

        // var length = kspSelect.options.length;
        // for (i = length - 1; i >= 0; i--) {
        //     kspSelect.options[i] = null;
        // }

        for (var i = 0; i < euSettings.KSPs.length; i++) {
            var opt = document.createElement("option");
            opt.appendChild(document.createTextNode(euSettings.KSPs[i].name));
            opt.value = euSettings.KSPs[i].ksp;
            kspSelect.appendChild(opt);
        }
        $("#pkKSPSelect").select2({
            width: '100%'
        });
    }

    SetCAs(e) {
        this.m_CAs = e;
        var caSelect = $("#pkCASelect"),
            casArr = [];
        if (
            null == this.m_CAs ||
            this.m_CAs.length < 2 //||
            // this.m_keyMediaType == KeyStoreType.KSP ||
            // this.m_keyMediaType == KeyStoreType.DIIA_UA ||
            // this.m_keyMediaType == KeyStoreType.DIIA_EU ||
            // this.m_formType == KeyOperations.MakeNewCertificate ||
            // this.m_formType == KeyOperations.MakeDeviceCertificate ||
            // this.m_ownCAOnly
        )
            $("#pkCABloсk").hide();
        else {
            caSelect.empty();

            casArr.push('Визначити автоматично');
            for (var r = 0; r < this.m_CAs.length; r++) {
                casArr.push(this.m_CAs[r].issuerCNs[0]);
            }
            $.each(casArr, function (e, n) {
                caSelect.append($("<option/>", {value: e, text: n}));
            });
            $("#pkCABloсk").show();
        }
        /*Оновимо селект вибору ЦСК*/
        $("#pkCASelect").select2({
            width: '100%'
        });

    }

    OnSelectPKeyFile(files) {
        let t = this;
        let isFiles = files && 1 == files.length;
        let fileName = isFiles ? files[0].name : "";
        let fileExtension = t.GetFileExtension(fileName);

        if (isFiles) {
            document.getElementById('PKeyFileReadBlock').classList.remove('show');
            document.getElementById('PKeyFileReadSelectedBlock').classList.add('show');
            document.getElementById('PKeyFileName').textContent = fileName;
        }
        if (isFiles && "jks" == fileExtension) {
            var readFileAliasSelect = $("#pkReadFileAliasSelect");
            readFileAliasSelect.empty();

            t.ReadFile(files[0])
                .then(function (e) {
                    return t
                        .GetCurrentLibrary()
                        .GetLibrary()
                        .GetJKSPrivateKeys(e.data);
                })
                .then(function (e) {
                    if (0 != e.length) {
                        $.each(e, function (n, r) {
                            var i = t.FilterUserCertificates(e[n].certificates);
                            readFileAliasSelect.append(
                                $("<option/>", {
                                    value: e[n].alias,
                                    text: e[n].alias + "(" + i[0].infoEx.subjCN + ")",
                                })
                            );
                        });
                        $('#pkReadFileAliasSelect').select2({
                            width: '100%'
                        });

                        readFileAliasSelect.prop("disabled", !1);
                        $("#pkReadFileSelectAliasBlock").show();
                    }
                })
                .catch(function (e) {
                        t.showStageErrors('fn_iit_module_init_key_stage', 1, 'Виникла помилка при отриманні інформації про особистий ключ\n' + e);
                    }
                );
        } else
            $("#pkReadFileSelectAliasBlock").hide(),
                $("#pkReadFileAliasSelect").empty();
    }

    FilterUserCertificates(e) {
        for (var t = new Array(), n = 0; n < e.length; n++)
            e[n].infoEx.subjType ==
            EndUserConstants.EndUserSubjectType.EndUser && t.push(e[n]);
        return t;
    }

    /**
     * Показує помилки які з'являются в етапі
     * @param element
     * @param show
     * @param message
     */
    showStageErrors(element, show = 0, message = '') {
        let errorBlock = document.getElementById(element).querySelector('.fn_error');
        errorBlock.querySelector('span').innerHTML = message;
        if (show) {
            errorBlock.classList.add('show');
        } else {
            errorBlock.classList.remove('show');
        }
    }

    GetFileExtension(e) {
        return e.substring(e.lastIndexOf(".") + 1, e.length);
    }


    ReadFile(e) {
        return new Promise(function (t, n) {
            var r = new FileReader();
            r.onloadend = function (n) {
                if (n.target.readyState == FileReader.DONE) {
                    var r = new FileWrapper(e.name, new Uint8Array(n.target.result), e);
                    t(r);
                }
            };
            r.readAsArrayBuffer(e);
        });
    }

    ReadFiles(e) {
        var t = this;

        return new Promise(function (n, r) {
            var i = Array(),
                o = 0,
                s = function () {
                    o >= e.length
                        ? n(i)
                        : (t
                            .ReadFile(e[o])
                            .then(function (e) {
                                i.push(e), s();
                            })
                            .catch(function (e) {
                                return r(e);
                            }),
                            o++);
                };
            s();
        });
    }

    SetSelectedCA(e) {
        for (var t = 0; t < this.m_CAs.length; t++) {
            for (var n = 0; n < this.m_CAs[t].issuerCNs.length; n++) {
                if (e == this.m_CAs[t].issuerCNs[n]) {
                    return void $("#pkCASelect").val(t + 1);
                }
            }
        }
    }

    IsQualifiedCertificates(e) {
        for (var t = 0; t < e.length; t++) {
            if (!e[t].infoEx.isPowerCert) {
                return false;
            }
        }

        return true;
    }

    SetViewPKeyInfo(e) {
        var t = this;
        if (e.certificatesInfo.length < 1)
            t.OnPKeyReaded(e);
        else {
            var certificatesInfo = e.certificatesInfo;
            var infoElement = certificatesInfo[0];

            // console.log(infoElement);
            document.getElementById('PKeyOwnerInfoSubjCN').textContent = infoElement.infoEx.subjCN;
            document.getElementById('PKeyOwnerInfoSubjOrg').textContent = infoElement.infoEx.subjOrg;
            document.getElementById('PKeyOwnerInfoSubjDRFOCode').textContent = infoElement.infoEx.subjDRFOCode;
            
            const code1 = document.getElementById('PKeyOwnerInfoSubjDRFOCode').textContent;
			const code2 = document.getElementById('PKeyOwnerInfoSubjDRFOCodeSelect').textContent;

			// Кнопка буде неактивною, якщо коди НЕ збігаються
			if (code1 !== code2) {
			    document.getElementById('BtnVerificationNext').style.display = 'none'; 
			    document.getElementById('PKeyOwnerInfoSubjDRFOCodeDescr').style.display = 'block';
			} else {
			    document.getElementById('BtnVerificationNext').style.display = 'block';
			    document.getElementById('PKeyOwnerInfoSubjDRFOCodeDescr').style.display = 'none'; 
			}
                           
        }
    }

    SetSignFileResult(
        filesData,
        sign,
        finalFileName,
        signsInfo,
        signersInfo,
        signContainerInfoType,
        signContainerInfoSubType,
        signContainerInfoAsicSignType
    ) {
        let file = new File([sign], finalFileName, {lastModified: new Date().getTime()});
        let container = new DataTransfer();
        container.items.add(file);
        document.getElementById('SignedFile').files = container.files;
        document.getElementById('SignedFileName').textContent = finalFileName;


        if (document.getElementById('InfoOwnerSignature')) {
            let data = {};
            Object.keys(signsInfo[0].ownerInfo).forEach(e => {
                data[e] = signsInfo[0].ownerInfo[e]
            })

            //Додамо мітки часу
            Object.keys(signsInfo[0].timeInfo).forEach(e => {
                data[e] = signsInfo[0].timeInfo[e]
            })
            console.log(data);
            document.getElementById('InfoOwnerSignature').value = JSON.stringify(data);
        }
    }


    OnPKeyReaded(ecpInfo) {
        switch (
            ((this.m_readedPKey = ecpInfo),
                this.ShowForm(null, !0),
                this.SetStatus(p(o.STATUS_PRIVATE_KEY_READED)),
                this.m_formType)
            ) {
            case KeyOperations.MakeNewCertificate:
                (this.m_isPKActionDone = !1), this.BeginUpdateKMs();
                break;
            case KeyOperations.ReadPrivateKey:
                this.m_isPKActionDone = !0;
                break;
            case KeyOperations.ViewPKeyCertificates:
                (this.m_isPKActionDone = !0), this.SetViewPKeyInfo(ecpInfo);
                break;
            case KeyOperations.SignFile:
                this.m_isPKActionDone = !0;
                var t = this.GetSupportedSignAlgos(ecpInfo.certificatesInfo);
                if (0 == t.length) {
                    var n = s.format(
                        p(o.ERROR_PRIVATE_KEY_INVALID_TYPE_OR_ALGO),
                        p(o.TEXT_KEY_USAGE_SIGN),
                        ""
                    );
                    return void this.SetError(n);
                }
                var r = $("#signAlgoSelect");
                r.empty(),
                    $.each(t, function (e, n) {
                        r.append(
                            $("<option/>", {value: t[e].value, text: t[e].text})
                        );
                    }),
                    this.ShowForm(
                        this.m_showSignTip ? "#preSignBlock" : "#signBlock",
                        !1
                    );
                break;
            case KeyOperations.MakeDeviceCertificate:
                this.m_isPKActionDone = !1;
        }
    }

    async SignFile() {
        let t = this;
        let currentLibrary = t.GetCurrentLibrary();

        let url = document.getElementById('FileToSign').href;
        //let fileName = (url.split('/').pop()).split('?').shift();
        
        let link = document.getElementById('FileToSign');
		let fileName = link.getAttribute('data-filename');

        setStatus(`Підписання файлу: ${fileName}`);

        let blob = await fetch(url)
            .then(r => r.blob())
            .catch(error => {
                    t.showStageErrors('fn_iit_module_file_signature_stage', 1, `Помилка підпису файлу: <strong>${fileName}</strong> </br> Помилка: ${error}`);
                    setStatus(`Помилка підпису файлу: <strong>${fileName}</strong> </br> Помилка: ${error}`, 1, 1)
                }
            );


        if (blob === undefined) {
            return;
        }
        if (blob.size > MAX_FILE_SIZE) {
            t.showStageErrors('fn_iit_module_file_signature_stage', 1, 'Розмір файлу для піпису занадто великий. Оберіть файл меншого розміру');
            setStatus('Розмір файлу для піпису занадто великий. Оберіть файл меншого розміру', 1, 1);
            return;
        }

        // let signTypesRadio = parseInt($("input[type='radio'][name=signTypesRadio]:checked").val());
        //Встановимо дефолтне значення форматам цифрового підпису CAdES
        let signTypesRadio = App.SignatureFormat.CAdES;

        // <option value="1">ДСТУ 4145</option>
        // let signAlgoSelect = parseInt($("#signAlgoSelect").val());
        let signAlgoSelect = 1;
        let signTypeCAdESSelect = parseInt($("#signTypeCAdESSelect").val());

        // <option value="144" class="i18n">CAdES-X LonclassNameовгостроковий з повними даними ЦСК для
        //     перевірки
        // </option>
        // <option value="16" class="i18n">CAdES-X LonclassNameовгостроковий з повними даними для
        //     перевірки
        // </option>
        // <option value="8" class="i18n">CAdES-C –&nclassNameодається посилання на повні дані для
        //     перевірки
        // </option>
        // <option value="4" class="i18n">CAdES-T – дclassNameься час підписання файлу КЕП</option>
        // <option value="1" class="i18n">CAdES-BES –classNameва перевірка достовірності і цілісності
        //     даних
        // </option>
        // let signFormatCAdESSelect = parseInt($("#signFormatCAdESSelect").val());
        let signFormatCAdESSelect = 144;

        let selectedXAdESFormat = parseInt($("#signFormatXAdESSelect").val());
        let signFormatPAdESSelect = parseInt($("#signFormatPAdESSelect").val());
        // let c = t.SignAlgoToHashAlgo(signAlgoSelect);

        let isASiC = signTypesRadio == App.SignatureFormat.ASiCS || signTypesRadio == App.SignatureFormat.ASiCE;
        let isPAdES = signTypesRadio == App.SignatureFormat.PAdES;
        let isXAdES = signTypesRadio == App.SignatureFormat.XAdES;


        let selectedXAdESType = parseInt($("#signTypeXAdESSelect").val());

        let asicContainerType = isASiC ?
            signTypesRadio == App.SignatureFormat.ASiCS
                ? EndUserConstants.EndUserASiCType.S
                : EndUserConstants.EndUserASiCType.E
            : EndUserConstants.EndUserASiCType.Unknown;


        // <option id="signTypeASiCCAdESOption" value="1" class="i18n">CAdES</option>
        // <option id="signTypeASiCXAdESOption" value="2" class="i18n">XAdES</option>
        // let selectedASiCType = parseInt($("#signTypeASiCSelect").val());
        let selectedASiCType = 1;

        let cadesSignFormat = selectedASiCType == EndUserConstants.EndUserASiCSignType.CAdES ?
            signFormatCAdESSelect : selectedXAdESFormat;

        let isCAdESDetached = signTypesRadio == App.SignatureFormat.CAdES && signTypeCAdESSelect == EndUserConstants.EndUserCAdESType.Detached;
        // let R = $("#signFilesInput").prop("files");
        // let firstFileName = R.length >= 1 ? R[0].name : "";
        let finalFileName = fileName + t.GetSignFileExt(fileName, signTypesRadio);
        // let filesTotalSize = t.GetFilesSize(R);
        let filesTotalSize = blob.size;
        let fileExtension = t.GetFileExtension(fileName).toLowerCase();
        // let signatureDataContainer = (new JSZip(),
        //     {
        //         filesData: null,
        //         namedData: null,
        //         hash: null,
        //         sign: null,
        //         signsInfo: null,
        //         signersInfo: null,
        //         signContainerInfo: null,
        //     });
        let signatureDataContainer = {
            filesData: null,
            namedData: null,
            hash: null,
            sign: null,
            signsInfo: null,
            signersInfo: null,
            signContainerInfo: null,
        };

        // 3. Створюємо об'єкт File з Blob
        const file = new File([blob], fileName, {
            type: blob.type,
            lastModified: new Date().getTime(),
        });

        // 4. Створюємо тимчасовий контейнер DataTransfer
        const dataTransfer = new DataTransfer();

        // 5. Додаємо наш File до контейнера
        dataTransfer.items.add(file);


        if (
            0 &&
            signTypesRadio != App.SignatureFormat.ASiCE &&
            (
                signTypesRadio != App.SignatureFormat.XAdES
                || selectedXAdESType != EndUserConstants.EndUserXAdESType.Detached
            )
        ) {

            let msg = 'Обраний формат підпису не підтримує одночасний підпис декількох файлів. Необхідно використовувати формат підпису ASiC-E';
            t.showStageErrors('fn_iit_module_file_signature_stage', 1, msg);
            setStatus(msg, 1, 1);
        } else if (
            (signTypesRadio != App.SignatureFormat.CAdES ||
                (signTypesRadio == App.SignatureFormat.CAdES &&
                    signTypeCAdESSelect != EndUserConstants.EndUserCAdESType.Detached)) &&
            filesTotalSize >= MAX_FILE_SIZE
        ) {
            let msg = 'Занадто великий розмір файлу. Оберіть файл меншого розміру або оберіть тип підпису "Дані та підпис окремими файлами (формат CAdES)"';
            t.showStageErrors('fn_iit_module_file_signature_stage', 1, msg);
            setStatus(msg, 1, 1);
        } else if (filesTotalSize <= 0) {
            let msg = 'Обраний файл не містить даних. Оберіть інший файл';
            t.showStageErrors('fn_iit_module_file_signature_stage', 1, msg);
            setStatus(msg, 1, 1);
        } else {
            switch (signTypesRadio) {
                case App.SignatureFormat.PAdES:
                    if ("pdf" != fileExtension) {
                        let msg = 'Формування підпису в форматі PAdES можливо лише для файлів pdf. Оберіть інший файл або формат підпису';
                        t.showStageErrors('fn_iit_module_file_signature_stage', 1, msg);
                        setStatus(msg, 1, 1);
                        return;
                    }
                    break;
                case App.SignatureFormat.XAdES:
                    if (
                        selectedXAdESType == i.EndUserConstants.EndUserXAdESType.Enveloped &&
                        "xml" != fileExtension
                    ) {

                        let msg = 'Формування підпису в форматі XAdES-enveloped можливо лише для файлів xml. Оберіть інший файл, формат або тип підпису';
                        t.showStageErrors('fn_iit_module_file_signature_stage', 1, msg);
                        setStatus(msg, 1, 1);
                        return;
                    }
            }

            this.NextStage('fn_iit_module_file_signature_stage');
            currentLibrary
                .GetLibrary()
                .SetRuntimeParameter(
                    EndUserConstants.EU_SIGN_TYPE_PARAMETER,
                    signFormatCAdESSelect
                )
                .then(function () {
                    return t.ReadFiles(dataTransfer.files);
                })
                .then(function (e) {
                    signatureDataContainer.filesData = e;
                    //     console.log([,isCAdESDetached]);
                    //     return isCAdESDetached ? currentLibrary.GetLibrary().HashData(c, e[0].data, !1) : null;
                    // })
                    // .then(function (e) {
                    //     signatureDataContainer.hash = e;
                    //     console.log(e);
                    //     console.log(1 == signatureDataContainer.filesData.length && isASiC);
                    //     return 1 == signatureDataContainer.filesData.length && isASiC
                    //         ? currentLibrary
                    //             .GetLibrary()
                    //             .GetSignContainerInfo(signatureDataContainer.filesData[0].data)
                    //         : null;
                    // })
                    // .then(function (containerInfo) {
                    // 1. Оновлення інформації про контейнер підпису
                    let containerInfo = null;
                    signatureDataContainer.signContainerInfo = containerInfo && containerInfo.type !== EndUserConstants.EndUserSignContainerType.Unknown
                        ? containerInfo
                        : null;

                    // 2. Перевірка, чи відповідає поточний контейнер підпису формату ASiC
                    const isASiCMatch = containerInfo
                        ? containerInfo.type === EndUserConstants.EndUserSignContainerType.ASiC &&
                        containerInfo.subType === asicContainerType &&
                        containerInfo.asicSignType === selectedASiCType
                        : false;

                    // 3. Форматування даних файлів
                    signatureDataContainer.namedData = [];
                    signatureDataContainer.filesData.forEach(function (e) {
                        signatureDataContainer.namedData.push({name: e.name, val: e.data});
                    });
                    // 4. Підготовка хешованих даних для від'єднаного підпису CAdES
                    const detachedCAdESData = isCAdESDetached
                        ? {name: signatureDataContainer.namedData[0].name, val: signatureDataContainer.hash}
                        : null;

                    // 5. Вибір і виклик відповідного методу підписання
                    const library = currentLibrary.GetLibrary();

                    if (isASiC) {
                        if (isASiCMatch) {
                            return library.ASiCAppendSign(signAlgoSelect, cadesSignFormat, null, signatureDataContainer.namedData[0], false);
                        } else {
                            return library.ASiCSignData(signAlgoSelect, asicContainerType, selectedASiCType, cadesSignFormat, signatureDataContainer.namedData, false);
                        }
                    } else if (isPAdES) {
                        return library.PDFSignData(signAlgoSelect, signatureDataContainer.namedData[0], signFormatPAdESSelect, false);
                    } else if (isXAdES) {
                        return library.XAdESSignData(signAlgoSelect, selectedXAdESType, selectedXAdESFormat, signatureDataContainer.namedData, false);
                    } else if (isCAdESDetached) {
                        return library.SignHash(signAlgoSelect, detachedCAdESData, true, false);
                    } else {
                        return library.SignDataEx(signAlgoSelect, signatureDataContainer.namedData[0], false, true, false);
                    }
                })
                .then(function (i) {
                    signatureDataContainer.sign = i.val;

                    setStatus('Перевірка підпису...')
                    const library = currentLibrary.GetLibrary();

                    if (isASiC) {
                        return library.ASiCVerifyData(signatureDataContainer.sign, -1);
                    } else if (isPAdES) {
                        return library.PDFVerifyData(signatureDataContainer.sign, -1);
                    } else if (isXAdES) {
                        return library.XAdESVerifyData(namedData, signatureDataContainer.sign, -1);
                    } else if (isCAdESDetached) {
                        return library.VerifyHash(hash, signatureDataContainer.sign, -1);
                    } else {
                        return library.VerifyDataInternal(signatureDataContainer.sign, -1);
                    }
                })
                .then(function (e) {
                    signatureDataContainer.signsInfo = e;

                    if (isASiC) {
                        return currentLibrary.GetLibrary().ASiCGetSigner(signatureDataContainer.sign, -1, !0)
                    } else if (isPAdES) {
                        return currentLibrary.GetLibrary().PDFGetSigner(signatureDataContainer.sign, -1, !0)
                    } else if (isXAdES) {
                        return currentLibrary.GetLibrary().XAdESGetSigner(signatureDataContainer.sign, -1, !0)
                    } else {
                        return currentLibrary.GetLibrary().GetSigner(signatureDataContainer.sign, -1, !0)
                    }
                })
                .then(function (e) {
                    signatureDataContainer.signersInfo = e;
                    return null != signatureDataContainer.signContainerInfo
                        ? signatureDataContainer.signContainerInfo
                        : currentLibrary.GetLibrary().GetSignContainerInfo(signatureDataContainer.sign);
                })
                .then(function (e) {
                    signatureDataContainer.signContainerInfo = e;
                    return signatureDataContainer.sign;
                })
                // .then(function (e) {
                //     console.log(['test2',e]);
                //     signatureDataContainer.sign = e;
                //     return e.m_signInfoTmpl ? e.m_signInfoTmpl : e.DownloadData("Data/sign-info.tmpl.xml", "");
                // })
                .then(function (e) {
                    // e.m_signInfoTmpl = e;
                    t.SetSignFileResult(
                        signatureDataContainer.filesData,
                        signatureDataContainer.sign,
                        finalFileName,
                        signatureDataContainer.signsInfo,
                        signatureDataContainer.signersInfo,
                        signatureDataContainer.signContainerInfo.type,
                        signatureDataContainer.signContainerInfo.subType,
                        signatureDataContainer.signContainerInfo.asicSignType
                    );
                    t.NextStage('fn_iit_module_init_qr_code');
                    t.NextStage('fn_iit_module_file_signature_stage', 'fn_iit_module_sending_signed_file_stage')
                    setStatus('Підпис успішно перевірено', 1);
                })
                .catch(function (error) {
                    t.StopOperationConfirmation();
                    t.NextStage('fn_iit_module_init_qr_code', 'fn_iit_module_file_signature_stage');
                    console.log('Помилка:', error.name);
                    console.log('Повідомлення:', error.message);
                    console.log('Деталі (стек):', error.stack);

                    let msg = 'Виникла помилка при підписі файла';
                    setStatus(msg, 0, 1);
                    t.showStageErrors('fn_iit_module_file_signature_stage', 1, msg);
                    setStatus(msg, 1, 1);
                });

        }
    }

    GetSignFileExt(e, t) {
        switch (t) {
            case App.SignatureFormat.CAdES:
                return ".p7s";
            case App.SignatureFormat.ASiCS:
                return ".asics";
            case App.SignatureFormat.ASiCE:
                return e.endsWith(".asice") || e.endsWith(".sce") ? "" : ".asice";
            case App.SignatureFormat.PAdES:
                return "";
            case App.SignatureFormat.XAdES:
                return e.endsWith(".xml") ? "" : ".xml";
            default:
                return "";
        }
    }


    OnReadPKeyKSP() {
        var t = this;
        let error;
        let currentLibrary = t.GetCurrentLibrary();

        if (t.m_isPKActionDone || null != t.m_readedPKey) {
            t.OnResetPKey();
        } else {
            setStatus('Зчитування особистого ключа');

            let n = 0;
            let r = null;
            let pkKSPUserId = null;
            let u = 0;
            let l = null;
            let ksPs = euSettings.KSPs;


            r = $("#pkKSPSelect").find(":selected").text();
            n = parseInt($("#pkKSPSelect").find(":selected").val());

            if (!(pkKSPUserId = $("#pkKSPUserId").val())) {
                // $("#pkKSPUserId").focus();
                error = 'Не вказано ідентифікатор користувача або він має невірний формат';
                setStatus(error, 1, 1);
                t.showStageErrors('fn_iit_module_init_key_stage', 1, error);
                return;
            }


            for (var i = 0; i < ksPs.length; i++)
                if (ksPs[i].name == r) {
                    for (var f = 0; f < t.m_CAs.length; f++)
                        if (t.m_CAs[f].codeEDRPOU == ksPs[i].codeEDRPOU) {
                            l = t.m_CAs[f].issuerCNs[0];
                            break;
                        }
                    break;
                }

            var E = new KeyInfo();
            E.keyMedia = null;
            E.password = null;
            E.kspKey = new KspKeyDetails(n, r, pkKSPUserId, u, l);

            currentLibrary.GetLibrary()
                .ReadPrivateKeyKSP(
                    E.kspKey.userId,
                    E.kspKey.GetKSPId(),
                    !0,
                    E.kspKey.keyId
                )
                .then(function (e) {
                    return null != e ||
                    confirm('Ви відмовилися надавати ідентифікаційні дані ос. ключа. Продовжити?')
                        ? currentLibrary.GetLibrary().GetOwnCertificates()
                        : null;
                })
                .then(function (n) {
                    t.NextStage('fn_iit_module_init_qr_code', 'fn_iit_module_init_key_stage');

                    setStatus('Зчитування особистого ключа');

                    if (!n) {
                        return currentLibrary.GetLibrary().ResetPrivateKey();
                    }

                    t.IsQualifiedCertificates(n) ||
                    euSettings.supportAdvancedCertificates
                        ? ((E.certificatesInfo = n), t.SetViewPKeyInfo(E))
                        : currentLibrary
                            .GetLibrary()
                            .ResetPrivateKey()
                            .then(function () {

                                t.StopOperationConfirmation();
                                var error = s.format(
                                    'Використання удосконаленого сертифіката відкритого ключа не дозволено. Зверніться до надавача Вашого сертифіката ({0}) для отримання квалфікованого сертифіката відкритого ключа',
                                    n[0].infoEx.issuerCN
                                );
                                setStatus(error, 0, 1);
                                t.showStageErrors('fn_iit_module_init_key_stage', 1, error);
                            })
                            .catch(function (e) {
                                throw e;
                            });
                })
                .then(function () {
                    t.StopOperationConfirmation();
                    /*Показуємо Етап 2 Інформація по ключу*/
                    t.NextStage('fn_iit_module_init_key_stage', 'fn_iit_module_data_verification_stage');
                    setStatus('Зчитування ключа успішно', true);
                })
                .catch(function (e) {
                    console.log('Помилка:', e.name);
                    console.log('Повідомлення:', e.message);
                    console.log('Деталі (стек):', e.stack);

                    error = 'Виникла помилка при зчитуванні особистого ключа';
                    setStatus(error, 0, 1);
                    t.NextStage('fn_iit_module_init_qr_code', 'fn_iit_module_init_key_stage');
                    t.showStageErrors('fn_iit_module_init_key_stage', 1, error);
                    setStatus(error, 1, 1);
                });
        }
    }

    /*Керування кроками*/
    NextStage(currentStage, nextStage) {
        if (currentStage) {
            let current = document.getElementById(currentStage);
            current.style.display = 'none';
        }
        if (nextStage) {
            let next = document.getElementById(nextStage);
            next.removeAttribute('style');
        }
    }

    BindEvents() {
        let t = this;
        document.getElementById("pkTypeFile").addEventListener(
            "click",
            function () {
                t.SetLibraryType(PK_FORM_TYPE_FILE);
            },
            false
        );

        // document.getElementById("pkTypeKeyMedia").addEventListener(
        //     "click",
        //     function () {
        //         t.SetLibraryType(PK_FORM_TYPE_KM);
        //     },
        //     false
        // );

        document.getElementById("pkTypeKSP").addEventListener(
            "click",
            function () {
                t.SetLibraryType(PK_FORM_TYPE_KSP);
            },
            false
        );

        document.getElementById("pkReadKSPSelect").addEventListener(
            "click",
            function () {
                t.OnReadPKeyKSP();
            },
            false
        );
        $("#pkKSPSelect").on("change.select2", function (t) {
            app.OnReadKSPSelectChange();
        });

        document.getElementById("pkReadFileInput").addEventListener(
            "change",
            function (e) {
                t.OnSelectPKeyFile(e.target.files);
            },
            false
        );

        document.getElementById("pkReadFileButton").addEventListener(
            "click",
            function (e) {
                t.OnReadPKey(true);
            },
            false
        );
    }

}

//===============================================================================

//===============================================================================
/**
 * Оновлює та заповнює випадаючий список (<select>) апаратних ключів.
 * @param _keyMedias
 */
function setKeyMedias(_keyMedias) {
    keyMedias = _keyMedias;

    var kmSelect = document.getElementById("pkKeyMediaSelect");

    var length = kmSelect.options.length;
    for (i = length - 1; i >= 0; i--) {
        kmSelect.options[i] = null;
    }

    for (var i = 0; i < keyMedias.length; i++) {
        var opt = document.createElement("option");
        opt.appendChild(document.createTextNode(keyMedias[i].visibleName));
        opt.value = keyMedias[i].visibleName;
        kmSelect.appendChild(opt);
    }
}

//===============================================================================

function getSelectedKeyMedia() {
    var kmSelected = document.getElementById("pkKeyMediaSelect").value;

    for (var i = 0; i < keyMedias.length; i++) {
        if (keyMedias[i].visibleName == kmSelected) return keyMedias[i];
    }

    return null;
}

//===============================================================================


//===============================================================================


//===============================================================================

/*
	Обробник сповіщень на підтвердження операції з використання ос. ключа 
	за допомогою сканування QR-коду в мобільному додатку сервісу підпису
*/
// function onConfirmKSPOperation(kspEvent) {
//     var node = "";
//     node += '<a href="' + encodeURI(kspEvent.url) + '" target="_blank">';
//     node +=
//         '<img src="data:image/bmp;base64,' +
//         kspEvent.qrCode +
//         '" style="padding: 10px; background: white;">';
//     node += "</a>";
//
//     document.getElementById("pkKSPQRImageBlock").innerHTML = node;
//     document.getElementById("pkKSPQRBlock").style.display = "block";
// }

//===============================================================================


//===============================================================================

// Ініціалізація бібліотеки
function initialize() {
    return new Promise(function (resolve, reject) {
        var isInitialized = false;

        // if (euSign == euSignFile) {
        euSign
            .IsInitialized()
            .then(function (result) {
                isInitialized = result;
                if (isInitialized) {
                    console.log("EndUser: JS library already initialized");
                    return;
                }

                console.log("EndUser: JS library initializing...");
                return euSign.Initialize(euSettings);
            })
            .then(function () {
                if (isInitialized) return;

                console.log("EndUser: JS library initialized");


                console.log("EndUser: event listener for KSPs registering...");

                return euSign.AddEventListener(
                    EndUserConstants.EndUserEventType.ConfirmKSPOperation,
                    onConfirmKSPOperation
                );
            })
            .then(function () {
                if (!isInitialized)
                    console.log("EndUser: event listener for KSPs registered");

                isInitialized = true;
                resolve();
            })
            .catch(function (e) {
                reject(e);
            });
        // } else {
        //     // Перевірка чи встановлені необхідні модулі для роботи криптографічної бібліотеки
        //     euSign
        //         .GetLibraryInfo()
        //         .then(function (result) {
        //             if (!result.supported) {
        //                 throw (
        //                     "Бібліотека web-підпису не підтримується " +
        //                     "в вашому браузері або ОС"
        //                 );
        //             }
        //
        //             if (!result.loaded) {
        //                 // Бібліотека встановлена, але потребує оновлення
        //                 if (result.isNativeLibraryNeedUpdate) {
        //                     throw (
        //                         "Бібліотека web-підпису потребує оновлення. " +
        //                         "Будь ласка, встановіть оновлення за посиланням " +
        //                         result.nativeLibraryInstallURL
        //                     );
        //                 }
        //
        //                 // Якщо браузер підтримує web-розширення рекомендується
        //                 // додатково до нативних модулів встановлювати web-розширення
        //                 // Увага! Встановлення web-розширень ОБОВ'ЯЗКОВЕ для ОС Linux та ОС Windows Server
        //                 if (
        //                     result.isWebExtensionSupported &&
        //                     !result.isWebExtensionInstalled
        //                 ) {
        //                     throw (
        //                         "Бібліотека web-підпису потребує встановлення web-розширення. " +
        //                         "Будь ласка, встановіть web-розширення за посиланням " +
        //                         result.webExtensionInstallURL +
        //                         " та оновіть сторінку"
        //                     );
        //                 }
        //
        //                 // Бібліотека (нативні модулі) не встановлені
        //                 throw (
        //                     "Бібліотека web-підпису потребує встановлення. " +
        //                     "Будь ласка, встановіть бібліотеку за посиланням " +
        //                     result.nativeLibraryInstallURL +
        //                     " та оновіть сторінку"
        //                 );
        //             }
        //
        //             return euSign.IsInitialized();
        //         })
        //         .then(function (result) {
        //             isInitialized = result;
        //             if (isInitialized) {
        //                 console.log("EndUser: SW library already initialized");
        //                 return;
        //             }
        //
        //             console.log("EndUser: SW library initializing...");
        //             return euSign.Initialize(euSettings);
        //         })
        //         .then(function () {
        //             if (!isInitialized) console.log("EndUser: SW library initialized");
        //
        //             resolve();
        //         })
        //         .catch(function (e) {
        //             reject(e);
        //         });
        // }
    });
}

//===============================================================================

function readPrivateKey() {
    var pkFileInput =
        formType == PK_FORM_TYPE_FILE ? document.getElementById("pkFile") : null;
    var passwordInput =
        formType != PK_FORM_TYPE_KSP
            ? document.getElementById(
            formType == PK_FORM_TYPE_FILE
                ? "pkFilePassword"
                : "pkKeyMediaPassword"
            )
            : null;
    var selectedKM = formType == PK_FORM_TYPE_KM ? getSelectedKeyMedia() : null;
    var kmSelect = document.getElementById("pkKeyMediaSelect");
    var ksp = formType == PK_FORM_TYPE_KSP ? getSelectedKSP() : null;
    var userIdInput =
        formType == PK_FORM_TYPE_KSP
            ? document.getElementById("pkKSPUserId")
            : null;
    /*
          Загальне ім'я ЦСК з списку CAs.json, який видав сертифікат для ос. ключа.
          Якщо null бібліотека намагається визначити ЦСК автоматично за
          сервером CMP\сертифікатом. Встановлюється у випадках, коли ЦСК не
          підтримує CMP, та для пришвидшення пошуку сертифіката ос. ключа
      */
    var caCN = null;
    /*
          Сертифікати, що відповідають ос. ключу (масив об'єктів типу Uint8Array).
          Якщо null бібліотека намагається завантажити їх з ЦСК автоматично з сервера CMP.
          Встановлюється у випадках, коли ЦСК не підтримує CMP, та для пришвидшення
          пошуку сертифіката ос. ключа
      */
    var pkCertificates = null;

    return new Promise(function (resolve, reject) {
        switch (formType) {
            case PK_FORM_TYPE_FILE:
                if (pkFileInput.value == null || pkFileInput.value == "") {
                    pkFileInput.focus();

                    reject("Не обрано файл з ос. ключем");

                    return;
                }

                if (passwordInput.value == null || passwordInput.value == "") {
                    passwordInput.focus();
                    reject("Не вказано пароль до ос. ключа");

                    return;
                }

                readFile(pkFileInput.files[0])
                    .then(function (result) {
                        console.log("Private key file readed");

                        // Якщо файл з ос. ключем має розширення JKS, ключ може містити декілька ключів,
                        // для зчитування такого ос. ключа необхіно обрати який ключ повинен зчитуватися
                        if (result.file.name.endsWith(".jks")) {
                            return euSign
                                .GetJKSPrivateKeys(result.data)
                                .then(function (jksKeys) {
                                    console.log("EndUser: jks keys got");

                                    // Для спрощення прикладу обирається перший ключ
                                    var pkIndex = 0;

                                    pkCertificates = [];
                                    for (var i = 0; i < jksKeys[pkIndex].certificates.length; i++)
                                        pkCertificates.push(jksKeys[pkIndex].certificates[i].data);

                                    return euSign.ReadPrivateKeyBinary(
                                        jksKeys[pkIndex].privateKey,
                                        passwordInput.value,
                                        pkCertificates,
                                        caCN
                                    );
                                });
                        }

                        return euSign.ReadPrivateKeyBinary(
                            result.data,
                            passwordInput.value,
                            pkCertificates,
                            caCN
                        );
                    })
                    .then(function (result) {
                        resolve(result);
                    })
                    .catch(function (e) {
                        reject(e);
                    });

                break;

            case PK_FORM_TYPE_KM:
                if (!selectedKM) {
                    kmSelect.focus();

                    reject("Не обрано носій з ос. ключем");

                    return;
                }

                if (passwordInput.value == null || passwordInput.value == "") {
                    passwordInput.focus();
                    reject("Не вказано пароль до ос. ключа");

                    return;
                }

                var keyMedia = new EndUserKeyMedia(selectedKM);
                keyMedia.password = passwordInput.value;

                euSign
                    .ReadPrivateKey(keyMedia, pkCertificates, caCN)
                    .then(function (result) {
                        resolve(result);
                    })
                    .catch(function (e) {
                        reject(e);
                    });

                break;

            case PK_FORM_TYPE_KSP:
                if (ksp == null) {
                    reject("Не обрано сервіс підпису");

                    return;
                }

                if (
                    !ksp.confirmationURL &&
                    (userIdInput.value == null || userIdInput.value == "")
                ) {
                    userIdInput.focus();

                    reject("Не вказано ідентифікатор користувача");

                    return;
                }

                document.getElementById("pkKSPQRImageLabel").innerHTML =
                    "Відскануйте QR-код для зчитування ос. ключа в моб. додатку:";

                euSign
                    .ReadPrivateKeyKSP(
                        !ksp.confirmationURL ? userIdInput.value : "",
                        ksp.name
                    )
                    .then(function (result) {
                        document.getElementById("pkKSPQRBlock").style.display = "none";
                        resolve(result);
                    })
                    .catch(function (e) {
                        document.getElementById("pkKSPQRBlock").style.display = "none";
                        reject(e);
                    });

                break;
        }
    });

}

//===============================================================================

function signData() {
    var dataInput = document.getElementById("data-textarea");
    var signInput = document.getElementById("sign-textarea");

    readPrivateKey()
        .then(function (result) {
            if (result) {
                console.log("EndUser: private key readed " + result.subjCN + ".");
            }

            if (formType == PK_FORM_TYPE_KSP) {
                document.getElementById("pkKSPQRImageLabel").innerHTML =
                    "Відскануйте QR-код для підпису в моб. додатку:";
            }

            return euSign.SignDataInternal(true, dataInput.value, true);
        })
        .then(function (sign) {
            console.log("EndUser: data signed");
            console.log("Data: " + dataInput.value);
            console.log("Sign: " + sign);

            signInput.value = sign;

            if (formType == PK_FORM_TYPE_KSP)
                document.getElementById("pkKSPQRBlock").style.display = "none";

            alert("Дані успішно підписані");
        })
        .catch(function (e) {
            if (formType == PK_FORM_TYPE_KSP)
                document.getElementById("pkKSPQRBlock").style.display = "none";

            var msg = e.message || e;

            console.log("Sign data error: " + msg);

            alert("Виникла помилка при підписі даних. " + "Опис помилки: " + msg);
        });
}

//===============================================================================

setStatus('Завантаження кріптографічної біблеотеки... ');
let app;
$(function () {
    // Ініціалізуємо основний об'єкт програми
    app = new App();
});

//===============================================================================
