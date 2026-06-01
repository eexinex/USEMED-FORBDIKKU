(function () {
  "use strict";

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  document.addEventListener("DOMContentLoaded", function () {
    document.body.classList.add("page-enter");

    setupPasswordToggle();
    setupDemoLogin();
    setupConfirmButtons();
    setupTableSearch();
    setupRiskForm();
    setupCopyButtons();
    setupPrintButtons();
    setupAutoHideAlert();
  });

  function setupPasswordToggle() {
    var inputs = qsa('input[type="password"]');

    inputs.forEach(function (input) {
      var parent = input.parentNode;

      if (!parent) {
        return;
      }

      if (!parent.classList.contains("input-wrap")) {
        var wrap = document.createElement("div");
        wrap.className = "input-wrap";

        parent.insertBefore(wrap, input);
        wrap.appendChild(input);
      }

      var container = input.parentNode;

      if (qs(".toggle-password", container)) {
        return;
      }

      var btn = document.createElement("button");
      btn.type = "button";
      btn.className = "toggle-password";
      btn.innerHTML = "👁";
      btn.setAttribute("aria-label", "toggle password");

      container.appendChild(btn);

      btn.addEventListener("click", function () {
        if (input.type === "password") {
          input.type = "text";
          btn.innerHTML = "🙈";
        } else {
          input.type = "password";
          btn.innerHTML = "👁";
        }
      });
    });
  }

  function setupDemoLogin() {
    var buttons = qsa("[data-demo-login]");

    buttons.forEach(function (button) {
      button.addEventListener("click", function () {
        var role = button.getAttribute("data-demo-login");

        var username = "";
        var password = "";

        if (role === "patient") {
          username = "HN0001";
          password = "123456";
        }

        if (role === "doctor") {
          username = "doctor1";
          password = "123456";
        }

        if (role === "admin") {
          username = "admin";
          password = "admin123";
        }

        var usernameInput =
          qs('input[name="username"]') ||
          qs('input[name="hn"]') ||
          qs('input[name="email"]');

        var passwordInput = qs('input[name="password"]');

        if (usernameInput) {
          usernameInput.value = username;
        }

        if (passwordInput) {
          passwordInput.value = password;
        }

        showToast("ใส่ข้อมูล Demo ให้แล้ว");
      });
    });
  }

  function setupConfirmButtons() {
    var elements = qsa("[data-confirm]");

    elements.forEach(function (el) {
      el.addEventListener("click", function (event) {
        var message = el.getAttribute("data-confirm") || "ยืนยันการทำรายการ?";
        var ok = window.confirm(message);

        if (!ok) {
          event.preventDefault();
        }
      });
    });
  }

  function setupTableSearch() {
    var inputs = qsa("[data-table-search]");

    inputs.forEach(function (input) {
      var tableId = input.getAttribute("data-table-search");
      var table = document.getElementById(tableId);

      if (!table) {
        return;
      }

      input.addEventListener("input", function () {
        var keyword = input.value.toLowerCase().trim();
        var rows = qsa("tbody tr", table);

        rows.forEach(function (row) {
          var text = row.textContent.toLowerCase();

          if (text.indexOf(keyword) !== -1) {
            row.style.display = "";
          } else {
            row.style.display = "none";
          }
        });
      });
    });
  }

  function setupRiskForm() {
    var form = qs("[data-risk-form]");
    var output = qs("[data-risk-output]");

    if (!form || !output) {
      return;
    }

    form.addEventListener("submit", function (event) {
      event.preventDefault();

      var age = getNumber(form, "age");
      var systolic = getNumber(form, "systolic");
      var diastolic = getNumber(form, "diastolic");
      var glucose = getNumber(form, "glucose");
      var hba1c = getNumber(form, "hba1c");
      var bmi = getNumber(form, "bmi");
      var cholesterol = getNumber(form, "cholesterol");

      var score = 0;
      var factors = [];

      if (age >= 60) {
        score += 10;
        factors.push("อายุมากกว่า 60 ปี");
      }

      if (systolic >= 160 || diastolic >= 100) {
        score += 25;
        factors.push("ความดันโลหิตสูงมาก");
      } else if (systolic >= 140 || diastolic >= 90) {
        score += 15;
        factors.push("ความดันโลหิตสูง");
      }

      if (glucose >= 200) {
        score += 25;
        factors.push("ระดับน้ำตาลสูงมาก");
      } else if (glucose >= 126) {
        score += 15;
        factors.push("ระดับน้ำตาลสูง");
      }

      if (hba1c >= 9) {
        score += 25;
        factors.push("HbA1c สูงมาก");
      } else if (hba1c >= 7) {
        score += 15;
        factors.push("HbA1c สูงกว่าค่าเป้าหมาย");
      }

      if (bmi >= 30) {
        score += 15;
        factors.push("BMI อยู่ในกลุ่มอ้วน");
      } else if (bmi >= 25) {
        score += 8;
        factors.push("BMI อยู่ในกลุ่มน้ำหนักเกิน");
      }

      if (cholesterol >= 240) {
        score += 10;
        factors.push("ไขมันในเลือดสูง");
      }

      if (score > 100) {
        score = 100;
      }

      var level = "ต่ำ";
      var color = "green";
      var summary = "ผู้ป่วยมีความเสี่ยงต่ำ แต่ควรดูแลสุขภาพต่อเนื่อง";

      if (score >= 70) {
        level = "สูง";
        color = "red";
        summary = "ผู้ป่วยมีความเสี่ยงสูง ควรติดตามอย่างใกล้ชิด";
      } else if (score >= 40) {
        level = "ปานกลาง";
        color = "orange";
        summary = "ผู้ป่วยมีความเสี่ยงปานกลาง ควรติดตามต่อเนื่อง";
      }

      if (factors.length === 0) {
        factors.push("ไม่พบปัจจัยเสี่ยงเด่นจากข้อมูลที่กรอก");
      }

      var listHtml = "";

      factors.forEach(function (item) {
        listHtml += "<li>" + escapeHtml(item) + "</li>";
      });

      output.innerHTML =
        '<div class="risk-card">' +
          '<div class="risk-score">' +
            '<div>' +
              '<span class="badge ' + color + '">Risk ' + level + '</span>' +
              '<h2 style="margin:12px 0 6px;">คะแนนความเสี่ยง ' + score + '/100</h2>' +
              '<p class="text-muted">' + summary + '</p>' +
            '</div>' +
            '<div class="score-circle" style="--value:' + score + '">' +
              '<strong>' + score + '</strong>' +
            '</div>' +
          '</div>' +
          '<div class="mt-2">' +
            '<div class="riskbar"><span style="width:' + score + '%"></span></div>' +
          '</div>' +
          '<ul class="factor-list">' + listHtml + '</ul>' +
        '</div>';

      showToast("คำนวณ AI Risk แล้ว");
    });
  }

  function setupCopyButtons() {
    var buttons = qsa("[data-copy]");

    buttons.forEach(function (button) {
      button.addEventListener("click", function () {
        var text = button.getAttribute("data-copy") || "";

        if (!text) {
          return;
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(text).then(function () {
            showToast("คัดลอกแล้ว");
          }).catch(function () {
            fallbackCopy(text);
          });
        } else {
          fallbackCopy(text);
        }
      });
    });
  }

  function fallbackCopy(text) {
    var input = document.createElement("textarea");
    input.value = text;
    document.body.appendChild(input);
    input.select();

    try {
      document.execCommand("copy");
      showToast("คัดลอกแล้ว");
    } catch (e) {
      showToast("คัดลอกไม่สำเร็จ");
    }

    input.remove();
  }

  function setupPrintButtons() {
    var buttons = qsa("[data-print]");

    buttons.forEach(function (button) {
      button.addEventListener("click", function () {
        window.print();
      });
    });
  }

  function setupAutoHideAlert() {
    var alerts = qsa(".alert");

    alerts.forEach(function (alert) {
      setTimeout(function () {
        alert.style.opacity = "0";
        alert.style.transform = "translateY(-8px)";
        alert.style.transition = "0.25s ease";

        setTimeout(function () {
          if (alert.parentNode) {
            alert.parentNode.removeChild(alert);
          }
        }, 300);
      }, 4200);
    });
  }

  function getNumber(form, name) {
    var input = form.querySelector('[name="' + name + '"]');

    if (!input) {
      return 0;
    }

    var number = Number(input.value);

    if (Number.isFinite(number)) {
      return number;
    }

    return 0;
  }

  function escapeHtml(text) {
    return String(text)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function showToast(message) {
    var box = qs(".toast-box");

    if (!box) {
      box = document.createElement("div");
      box.className = "toast-box";
      box.style.position = "fixed";
      box.style.right = "18px";
      box.style.bottom = "18px";
      box.style.zIndex = "9999";
      box.style.display = "grid";
      box.style.gap = "10px";
      document.body.appendChild(box);
    }

    var item = document.createElement("div");
    item.textContent = message;
    item.style.padding = "13px 15px";
    item.style.borderRadius = "16px";
    item.style.color = "#fff";
    item.style.background = "linear-gradient(135deg,#0f9f85,#087662)";
    item.style.boxShadow = "0 16px 34px rgba(8,72,63,.20)";
    item.style.fontWeight = "800";
    item.style.opacity = "0";
    item.style.transform = "translateY(8px)";
    item.style.transition = "0.2s ease";

    box.appendChild(item);

    setTimeout(function () {
      item.style.opacity = "1";
      item.style.transform = "translateY(0)";
    }, 20);

    setTimeout(function () {
      item.style.opacity = "0";
      item.style.transform = "translateY(8px)";

      setTimeout(function () {
        if (item.parentNode) {
          item.parentNode.removeChild(item);
        }
      }, 250);
    }, 2200);
  }
})();