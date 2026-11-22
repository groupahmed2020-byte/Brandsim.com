const SKY_URL = "https://gentle-morning-4759.groupahmed2020.workers.dev/",
      ALOHA_URL = "https://solitary-union-39c9.groupahmed2020.workers.dev/",
      AYLOL_URL = "https://steep-shadow-66a0.groupahmed2020.workers.dev/",
      AREEN_URL = "https://script.google.com/macros/s/AKfycbxidpoKH8PnhxSvKggQpI2K2BiQQLm7PJM2byXFxnJY1f2Iahqj4UdRfYFuECuqqoPK/exec",
      QUICK_URL = "https://script.google.com/macros/s/AKfycbzoFY7vXfDNsMBieM33Ohym4o3oif6_hoc1FI0RFzKbEgvfALOVMBWQKNt14VOakcQg/exec",
      LAYAN_URL = "https://api.layan-t.net/api/Subscribtions/CustomersRefreshNumber",
      QUERY_URL = "https://incredible-crumble-58566c.netlify.app/.netlify/functions/query",
      CUSTOMERS_JSON_URL = "https://incredible-crumble-58566c.netlify.app/customers.json",
      CUSTOM_JSON_URL = "https://incredible-crumble-58566c.netlify.app/custom.json";

function jsonOut(payload) {
  return ContentService.createTextOutput(JSON.stringify(payload)).setMimeType(ContentService.MimeType.JSON);
}

function doGet(e) {
  return jsonOut({ ok: true, msg: "BRAND SIM API v19 - Isolated Gateways" });
}

function clean(text) {
  return String(text || "")
    .replace(/Sky\s*Telecom|SkyTelecom|سكاي\s*تيليكوم|سكاي\s*تليكوم|سكاي/gi, "BRAND SIM")
    .replace(/Layan|ليان|Areen|أرين|ارين|Quick\s*Line|كويك\s*لاين|Aylol|أيلول|ايلول|Aloha|ألوها|الوها/gi, "BRAND SIM")
    .replace(/[\u0000-\u001F\u007F-\u009F<>]/g, "")
    .trim()
    .slice(0, 350);
}

function normPhone(raw) {
  let s = String(raw || "").replace(/[^0-9]/g, "");
  if (s.indexOf("00972") === 0 || s.indexOf("00970") === 0) s = "0" + s.slice(5);
  else if ((s.indexOf("972") === 0 || s.indexOf("970") === 0) && s.length >= 11) s = "0" + s.slice(3);
  else if (s.length === 9 && s.indexOf("5") === 0) s = "0" + s;
  return s;
}

function findInJsonUrl(url, phone) {
  try {
    const res = UrlFetchApp.fetch(url + "?t=" + Date.now(), { method: "get", muteHttpExceptions: true });
    if (res.getResponseCode() === 200) {
      const parsed = JSON.parse(res.getContentText());
      const list = Array.isArray(parsed) ? parsed : (parsed.customers || parsed.data || []);
      if (Array.isArray(list)) {
        for (let i = 0; i < list.length; i++) {
          const item = list[i];
          if (normPhone(item.simPhone || item.phone || item.number || "") === phone) {
            return item;
          }
        }
      }
    }
  } catch (err) {}
  return null;
}

function detectGateway(customer) {
  const sourceFields = [
    customer.source,
    customer.sourceProvider,
    customer.subCompany,
    customer.provider,
    customer.agentName
  ].join(" ").toLowerCase();

  if (/الوها|ألوها|aloha/.test(sourceFields)) return "ALOHA";
  if (/سكاي|sky/.test(sourceFields)) return "SKY";
  if (/ارين|أرين|areen/.test(sourceFields)) return "AREEN";
  if (/كويك|quick/.test(sourceFields)) return "QUICKLINE";
  if (/ايلول|أيلول|aylol/.test(sourceFields)) return "AYLOL";
  if (/ليان|layan/.test(sourceFields)) return "LAYAN";

  const pkgFields = [customer.company, customer.packageInfo].join(" ").toLowerCase();
  if (/smile\s*sim|yasmeen\s*telecom|aloha|ألوها|الوها/.test(pkgFields)) return "ALOHA";
  if (/\bal\b|مدخل سكاي|سكاي|sky/.test(pkgFields)) return "SKY";

  return "UNKNOWN";
}

function refreshAloha(phone) {
  const res = UrlFetchApp.fetch(ALOHA_URL + "?num=" + encodeURIComponent(phone), { method: "get", muteHttpExceptions: true });
  const rawText = res.getContentText();
  try {
    const data = JSON.parse(rawText);
    return {
      ok: Boolean(data && (data.status === true || data.ok === true || data.success === true)),
      msg: clean(data.message || data.msg || "")
    };
  } catch (err) {
    return {
      ok: res.getResponseCode() >= 200 && res.getResponseCode() < 300,
      msg: clean(rawText)
    };
  }
}

function refreshSky(phone) {
  const res = UrlFetchApp.fetch(SKY_URL + "?text=" + encodeURIComponent("SKY_WEB:" + phone) + "&timestamp=" + Date.now(), { method: "get", muteHttpExceptions: true });
  return {
    ok: res.getResponseCode() >= 200 && res.getResponseCode() < 300,
    msg: clean(res.getContentText())
  };
}

function refreshAreen(phone) {
  const res = UrlFetchApp.fetch(AREEN_URL, {
    method: "post",
    contentType: "text/plain;charset=utf-8",
    payload: JSON.stringify({ MobNumber: phone, provider: "areen" }),
    muteHttpExceptions: true
  });
  const data = JSON.parse(res.getContentText());
  const code = data.StatusCode !== undefined ? data.StatusCode : (data.statusCode !== undefined ? data.statusCode : (data.Status !== undefined ? data.Status : data.status));
  return {
    ok: Number(code) === 23 || Boolean(data.IsSuccess !== undefined ? data.IsSuccess : data.isSuccess),
    msg: clean(data.Message || data.message || data.ErrorMessage || data.errorMessage || "")
  };
}

function refreshQuickline(phone) {
  const res = UrlFetchApp.fetch(QUICK_URL, {
    method: "post",
    contentType: "text/plain;charset=utf-8",
    payload: JSON.stringify({ phoneNumber: phone, provider: "quickline" }),
    muteHttpExceptions: true
  });
  const data = JSON.parse(res.getContentText());
  return {
    ok: data.StatusCode >= 200 && data.StatusCode < 300,
    msg: clean(String(data.Message || "").replace(/^"|"$/g, ""))
  };
}

function refreshAylol(phone) {
  const res = UrlFetchApp.fetch(AYLOL_URL, {
    method: "post",
    contentType: "application/x-www-form-urlencoded",
    payload: "phone=" + encodeURIComponent(phone),
    muteHttpExceptions: true
  });
  const data = JSON.parse(res.getContentText());
  return {
    ok: Boolean(data && data.success),
    msg: clean(data ? data.message : "")
  };
}

function refreshLayan(phone, isHe) {
  const res = UrlFetchApp.fetch(LAYAN_URL, {
    method: "post",
    contentType: "application/json",
    headers: { Accept: "application/json, text/plain, */*", LANG: isHe ? "he" : "ar" },
    payload: JSON.stringify({ number: phone, provider: "layan" }),
    muteHttpExceptions: true
  });
  const statusCode = res.getResponseCode();
  let rawText = res.getContentText();
  try {
    const parsed = JSON.parse(rawText);
    rawText = typeof parsed === "string" ? parsed : (parsed.message || parsed.title || parsed.error || rawText);
  } catch (err) {
    rawText = rawText.replace(/^"|"$/g, "");
  }
  return {
    ok: statusCode >= 200 && statusCode < 300,
    msg: clean(rawText)
  };
}

function doPost(e) {
  try {
    const rawBody = (e && e.postData && e.postData.contents) ? e.postData.contents : "{}";
    const body = JSON.parse(rawBody);
    const phone = normPhone(body.phone);
    const isHe = body.lang === "he";

    if (!/^05\d{8}$/.test(phone)) {
      return jsonOut({
        ok: false,
        type: "warning",
        msg: isHe ? "נא להזין מספר טלפון תקין בן 10 ספרות המתחיל ב-05" : "يرجى إدخال رقم هاتف صحيح مكون من 10 أرقام ويبدأ بـ 05"
      });
    }

    let customer = body.provider ? { provider: body.provider } : null;
    try {
      const qRes = UrlFetchApp.fetch(QUERY_URL, {
        method: "post",
        contentType: "application/json",
        payload: JSON.stringify({ phone: phone }),
        muteHttpExceptions: true
      });
      if (qRes.getResponseCode() === 200) {
        const parsed = JSON.parse(qRes.getContentText());
        if (parsed && parsed.ok && parsed.data) customer = Object.assign({}, customer || {}, parsed.data);
      }
    } catch (err) {}

    if (!customer || detectGateway(customer) === "UNKNOWN") {
      const fromCustomers = findInJsonUrl(CUSTOMERS_JSON_URL, phone);
      if (fromCustomers) {
        customer = Object.assign({}, customer || {}, fromCustomers);
      } else if (!customer) {
        customer = findInJsonUrl(CUSTOM_JSON_URL, phone);
      }
    }

    if (!customer) {
      return jsonOut({
        ok: false,
        type: "error",
        msg: isHe ? "מספר זה אינו רשום במערכת BRAND SIM ולא ניתן לרענן אותו." : "عذراً، هذا الرقم غير مسجل في منظومة براند سيم (BRAND SIM) ولا يمكن تحديثه."
      });
    }

    const gateway = detectGateway(customer);
    let result = { ok: false, msg: "" };

    switch (gateway) {
      case "ALOHA":
        result = refreshAloha(phone);
        break;
      case "SKY":
        result = refreshSky(phone);
        break;
      case "AREEN":
        result = refreshAreen(phone);
        break;
      case "QUICKLINE":
        result = refreshQuickline(phone);
        break;
      case "AYLOL":
        result = refreshAylol(phone);
        break;
      case "LAYAN":
        result = refreshLayan(phone, isHe);
        break;
      default:
        return jsonOut({
          ok: false,
          type: "error",
          msg: isHe ? "לא זוهתה ספקית השירות עבור מספר זה." : "تعذر تحديد بوابة الشركة المشغلة لهذا الرقم، يرجى مراجعة الإدارة."
        });
    }

    const defaultSuccess = isHe ? "✅ המספר רוענן בהצלחה!" : "✅ تم إرسال طلب تحديث وتنشيط الرقم بنجاح!";
    const defaultFail = isHe ? "⚠️ פעולת הרענון נכשלה, בדוק את המספר או נסה שוב." : "⚠️ تعذر تحديث الرقم حالياً، يرجى التأكد من الرقم أو المحاولة لاحقاً.";

    return jsonOut({
      ok: result.ok,
      type: result.ok ? "success" : "warning",
      gateway: gateway,
      msg: result.msg || (result.ok ? defaultSuccess : defaultFail)
    });
  } catch (err) {
    return jsonOut({ ok: false, type: "error", msg: "⚠️ تعذر تحديث الرقم حالياً، يرجى المحاولة لاحقاً." });
  }
}
