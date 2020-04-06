!(function(_, e) {
    "object" == typeof exports && "undefined" != typeof module
        ? (module.exports = e(require("dayjs")))
        : "function" == typeof define && define.amd
        ? define(["dayjs"], e)
        : (_.dayjs_locale_ka = e(_.dayjs));
})(this, function(_) {
    "use strict";
    _ = _ && _.hasOwnProperty("default") ? _.default : _;
    var e = {
        name: "ka",
        weekdays: "კვირა_ორშაბათი_სამშაბათი_ოთხშაბათი_ხუთშაბათი_პარასკევი_შაბათი".split(
            "_"
        ),
        weekdaysShort: "კვი_ორშ_სამ_ოთხ_ხუთ_პარ_შაბ".split("_"),
        weekdaysMin: "კვ_ორ_სა_ოთ_ხუ_პა_შა".split("_"),
        months: "იანვარი_თებერვალი_მარტი_აპრილი_მაისი_ივნისი_ივლისი_აგვისტო_სექტემბერი_ოქტომბერი_ნოემბერი_დეკემბერი".split(
            "_"
        ),
        monthsShort: "იან_თებ_მარ_აპრ_მაი_ივნ_ივლ_აგვ_სექ_ოქტ_ნოე_დეკ".split(
            "_"
        ),
        weekStart: 1,
        formats: {
            LT: "h:mm A",
            LTS: "h:mm:ss A",
            L: "DD/MM/YYYY",
            LL: "D MMMM YYYY",
            LLL: "D MMMM YYYY h:mm A",
            LLLL: "dddd, D MMMM YYYY h:mm A"
        },
        relativeTime: {
            future: "%s შემდეგ",
            past: "%s წინ",
            s: "%d წამის",
            m: "1 წუთის",
            mm: "%d წუთის",
            h: "1 საათის",
            hh: "%d საათის",
            d: "1 დღის",
            dd: "%d დღის",
            M: "1 თვის",
            MM: "%d თვის",
            y: "1 წლის",
            yy: "%d წლის"
        },
        ordinal: function(_) {
            return _;
        }
    };
    return _.locale(e, null, !0), e;
});
