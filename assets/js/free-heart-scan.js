/**
 * Free Heart Scan - Interactive Form Script (Standalone WordPress)
 */
(function ($) {
  "use strict";

  $(document).ready(function () {
    const $wrapper = $(".fhs-wrapper");
    if (!$wrapper.length) return;

    let currentStep = 1;
    const totalSteps = 2;

    // Phone Auto-Formatter: (XXX) XXX-XXXX
    $wrapper.on("input", "#fhs-phone", function () {
      let val = $(this).val().replace(/\D/g, "");
      if (val.length > 10) val = val.substring(0, 10);
      let formatted = "";
      if (val.length > 6) {
        formatted = `(${val.substring(0, 3)}) ${val.substring(3, 6)}-${val.substring(6)}`;
      } else if (val.length > 3) {
        formatted = `(${val.substring(0, 3)}) ${val.substring(3)}`;
      } else if (val.length > 0) {
        formatted = `(${val}`;
      }
      $(this).val(formatted);
    });

    // Step Navigation
    function showStep(step) {
      $wrapper.find(".fhs-step").removeClass("active");
      $wrapper.find(`.fhs-step[data-step="${step}"]`).addClass("active");

      const percent = (step / totalSteps) * 100;
      $wrapper.find(".fhs-progress-bar-fill").css("width", `${percent}%`);
      $wrapper
        .find(".fhs-step-indicator")
        .text(`Step ${step} of ${totalSteps}`);

      currentStep = step;
      window.scrollTo({ top: $wrapper.offset().top - 40, behavior: "smooth" });
    }

    // Step 1 Validation
    function validateStep1() {
      let valid = true;
      const firstName = $("#fhs-first-name").val().trim();
      const lastName = $("#fhs-last-name").val().trim();
      const email = $("#fhs-email").val().trim();
      const phone = $("#fhs-phone").val().replace(/\D/g, "");
      const zip = $("#fhs-zip").val().trim();
      const age = $("#fhs-age").val();

      // Reset errors
      $wrapper.find(".fhs-input, .fhs-select").removeClass("error");
      $wrapper.find(".fhs-field-error").hide();

      if (!firstName) {
        showError("#fhs-first-name", "Please enter your first name.");
        valid = false;
      }
      if (!lastName) {
        showError("#fhs-last-name", "Please enter your last name.");
        valid = false;
      }
      if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showError("#fhs-email", "Please enter a valid email address.");
        valid = false;
      }
      if (!phone || phone.length < 10) {
        showError("#fhs-phone", "Please enter a 10-digit phone number.");
        valid = false;
      }
      if (!zip || !/^\d{5}$/.test(zip)) {
        showError("#fhs-zip", "Please enter a valid 5-digit ZIP code.");
        valid = false;
      }
      if (!age) {
        showError("#fhs-age", "Please select your age range.");
        valid = false;
      }

      return valid;
    }

    // Step 2 Validation
    function validateStep2() {
      let valid = true;
      const jobTitle = $("#fhs-job-title").val().trim();
      const years = $("#fhs-years").val();
      const stress = $("#fhs-stress").val();

      if (!jobTitle) {
        showError("#fhs-job-title", "Please enter your current job title.");
        valid = false;
      }
      if (!years) {
        showError("#fhs-years", "Please select your years of experience.");
        valid = false;
      }
      if (!stress) {
        showError("#fhs-stress", "Please select your work stress level.");
        valid = false;
      }

      return valid;
    }

    function showError(selector, msg) {
      const $el = $(selector);
      $el.addClass("error");
      $el.closest(".fhs-form-group").find(".fhs-field-error").text(msg).show();
    }

    // Next Button Click
    $wrapper.on("click", ".fhs-btn-next", function (e) {
      e.preventDefault();
      if (validateStep1()) {
        showStep(2);
      }
    });

    // Prev Button Click
    $wrapper.on("click", ".fhs-btn-prev", function (e) {
      e.preventDefault();
      showStep(1);
    });

    // Form Submit
    $wrapper.on("submit", "#fhs-intake-form", function (e) {
      e.preventDefault();
      if (!validateStep2()) return;

      const $submitBtn = $wrapper.find(".fhs-btn-submit");
      $submitBtn
        .prop("disabled", true)
        .html('<span class="fhs-spinner"></span> Evaluating...');

      const payload = {
        first_name: $("#fhs-first-name").val().trim(),
        last_name: $("#fhs-last-name").val().trim(),
        email: $("#fhs-email").val().trim(),
        phone: $("#fhs-phone").val().replace(/\D/g, ""),
        zip_code: $("#fhs-zip").val().trim(),
        age_range: $("#fhs-age").val(),
        job_title: $("#fhs-job-title").val().trim(),
        years_in_position: $("#fhs-years").val(),
        current_or_prior_work_related_stress_level: $("#fhs-stress").val(),
        employment_status: $("#fhs-employment").val(),
        industry: $("#fhs-industry").val(),
      };

      console.group(
        "%c[1/2] [Initiating Lead Registration - WordPress Native]",
        "background: #262c60; color: #fff; font-weight: bold; padding: 4px 8px; border-radius: 4px;",
      );
      console.log("📋 Captured Lead Data:", payload);
      console.log(
        "🤖 Calculating salary and income range with WordPress backend...",
      );
      console.groupEnd();

      // Submit to WordPress REST API with fallback to admin-ajax
      $.ajax({
        url: fhsConfig.apiUrl,
        method: "POST",
        headers: {
          "X-WP-Nonce": fhsConfig.nonce,
          "Content-Type": "application/json",
        },
        data: JSON.stringify(payload),
        success: function (res) {
          handleSuccess(res, payload);
        },
        error: function () {
          // Fallback to admin-ajax.php
          $.ajax({
            url: fhsConfig.ajaxUrl,
            method: "POST",
            data: {
              action: "fhs_submit_lead",
              nonce: fhsConfig.ajaxNonce,
              ...payload,
            },
            success: function (res) {
              handleSuccess(res, payload);
            },
            error: function (err) {
              $submitBtn.prop("disabled", false).text("Retry Submission");
              alert(
                "An error occurred while processing your request. Please try again.",
              );
            },
          });
        },
      });
    });

    const REGIONS_DATABASE = {
      jupiter: {
        name: "Jupiter",
        matchLocation: {
          title: "Life Imaging - Jupiter",
          address: "311 W Indiantown Rd., Jupiter, FL 33458",
          phone: "954-834-6362",
        },
        options: [
          {
            name: "Life Imaging - Jupiter",
            address: "311 W Indiantown Rd.\nJupiter, FL 33458",
            phone: "954-834-6362",
            website: "lifeimagingfla.com",
            websiteUrl: "https://lifeimagingfla.com",
            hasCoupon: true,
            couponUrl: "https://www.groupon.com/deals/life-imaging-fla-1",
          },
          {
            name: "Jupiter Medical Center",
            address: "1210 S Old Dixie Hwy\nJupiter, FL 33458",
            phone: "",
            website: "jupitermed.com",
            websiteUrl: "https://jupitermed.com",
            hasCoupon: false,
          },
          {
            name: "Jupiter Medical Center Imaging",
            address: "2111 Military Trail, Suite 105\nJupiter, FL 33458",
            phone: "",
            website: "jupitermed.com/services/imaging/",
            websiteUrl: "https://jupitermed.com/services/imaging/",
            hasCoupon: false,
          },
        ],
      },
      orlando: {
        name: "Orlando",
        matchLocation: {
          title: "Life Imaging - Orlando",
          address: "930 South Orange Avenue, Orlando, FL 32806",
          phone: "954-834-6362",
        },
        options: [
          {
            name: "Life Imaging - Orlando",
            address: "930 South Orange Avenue\nOrlando, FL 32806",
            phone: "954-834-6362",
            website: "lifeimagingfla.com",
            websiteUrl: "https://lifeimagingfla.com",
            hasCoupon: true,
            couponUrl: "https://www.groupon.com/deals/life-imaging-fla-1",
          },
          {
            name: "AdventHealth Orlando Imaging",
            address: "601 E Rollins St, 1st Floor\nOrlando, FL 32803",
            phone: "",
            website: "adventhealth.com",
            websiteUrl: "https://adventhealth.com",
            hasCoupon: false,
          },
          {
            name: "Orlando Health Imaging Centers",
            address: "1800 S Orange Ave.\nOrlando, FL 32806",
            phone: "",
            website: "orlandohealth.com",
            websiteUrl: "https://orlandohealth.com",
            hasCoupon: false,
          },
        ],
      },
      miami: {
        name: "Miami",
        matchLocation: {
          title: "Life Imaging - Miami",
          address: "2344 S Douglas Road, Coral Gables, FL 33134",
          phone: "954-834-6362",
        },
        options: [
          {
            name: "Life Imaging - Miami",
            address: "2344 S Douglas Road\nCoral Gables, FL 33134",
            phone: "954-834-6362",
            website: "lifeimagingfla.com",
            websiteUrl: "https://lifeimagingfla.com",
            hasCoupon: true,
            couponUrl: "https://www.groupon.com/deals/life-imaging-fla-1",
          },
          {
            name: "Baptist Health South Florida",
            address: "19955 NW 27 Ave., Suite 120\nMiami Gardens, FL 33056",
            phone: "",
            website: "baptisthealth.net",
            websiteUrl: "https://baptisthealth.net",
            hasCoupon: false,
          },
          {
            name: "Memorial Healthcare System",
            address: "3501 Johnson St.\nHollywood, FL 33021",
            phone: "",
            website: "mhs.net",
            websiteUrl: "https://mhs.net",
            hasCoupon: false,
          },
        ],
      },
      jacksonville: {
        name: "Jacksonville",
        matchLocation: {
          title: "Life Imaging - Jacksonville",
          address: "9965 San Jose Blvd, Jacksonville, FL",
          phone: "954-834-6362",
        },
        options: [
          {
            name: "Life Imaging - Jacksonville",
            address: "9965 San Jose Blvd\nJacksonville, FL",
            phone: "954-834-6362",
            website: "lifeimagingfla.com",
            websiteUrl: "https://lifeimagingfla.com",
            hasCoupon: true,
            couponUrl: "https://www.groupon.com/deals/life-imaging-fla-1",
          },
          {
            name: "Baptist Health Jacksonville Imaging",
            address: "800 Prudential Dr\nJacksonville, FL 32207",
            phone: "",
            website: "baptistjax.com",
            websiteUrl: "https://baptistjax.com",
            hasCoupon: false,
          },
        ],
      },
      deerfield: {
        name: "Deerfield Beach",
        matchLocation: {
          title: "Life Imaging - Deerfield Beach",
          address: "1981 W Hillsboro Boulevard, Deerfield Beach, FL 33442",
          phone: "954-834-6362",
        },
        options: [
          {
            name: "Life Imaging - Deerfield Beach",
            address: "1981 W Hillsboro Boulevard\nDeerfield Beach, FL 33442",
            phone: "954-834-6362",
            website: "lifeimagingfla.com",
            websiteUrl: "https://lifeimagingfla.com",
            hasCoupon: true,
            couponUrl: "https://www.groupon.com/deals/life-imaging-fla-1",
          },
          {
            name: "Broward Health North Imaging",
            address: "201 E Sample Rd\nDeerfield Beach, FL 33064",
            phone: "",
            website: "browardhealth.org",
            websiteUrl: "https://browardhealth.org",
            hasCoupon: false,
          },
        ],
      },
    };

    function getRegionKey(clinic, zip) {
      const clinicName = (
        clinic && clinic.name ? clinic.name : ""
      ).toLowerCase();
      const clinicCity = (
        clinic && clinic.city ? clinic.city : ""
      ).toLowerCase();

      if (clinicName.includes("jupiter") || clinicCity.includes("jupiter"))
        return "jupiter";
      if (clinicName.includes("orlando") || clinicCity.includes("orlando"))
        return "orlando";
      if (
        clinicName.includes("miami") ||
        clinicCity.includes("coral gables") ||
        clinicCity.includes("miami")
      )
        return "miami";
      if (
        clinicName.includes("jacksonville") ||
        clinicCity.includes("jacksonville")
      )
        return "jacksonville";
      if (clinicName.includes("deerfield") || clinicCity.includes("deerfield"))
        return "deerfield";

      const cleanZip = parseInt((zip || "33134").replace(/\D/g, ""), 10);
      if (cleanZip >= 32000 && cleanZip <= 32299) return "jacksonville";
      if (cleanZip >= 32700 && cleanZip <= 32999) return "orlando";
      if (cleanZip >= 33400 && cleanZip <= 33499) return "jupiter";
      if (cleanZip >= 33000 && cleanZip <= 33099) return "deerfield";
      return "miami";
    }

    function handleSuccess(res, payload) {
      if (res && res.success === false) {
        const $submitBtn = $wrapper.find(".fhs-btn-submit");
        $submitBtn.prop("disabled", false).text("Retry Submission");
        alert(
          res.error ||
            "An error occurred while processing your request. Please try again.",
        );
        return;
      }

      console.group(
        "%c[2/2] [CRM Payload Sent Successfully - WordPress Plugin]",
        "background: #15803d; color: #fff; font-size: 12px; font-weight: bold; padding: 4px 8px; border-radius: 4px;",
      );
      console.log("🎯 Confirmation ID:", res.lead_id);
      console.log(
        "%c💰 INCOME_RANGE CALCULATED AND SENT TO CRM: " +
          res.income_range_used,
        "background: #0284c7; color: #fff; font-weight: bold; padding: 2px 6px; border-radius: 3px;",
      );
      if (res.estimated_income) {
        console.log(
          "%c💵 Estimated Annual Salary: $" +
            Number(res.estimated_income).toLocaleString("en-US") +
            " USD",
          "color: #059669; font-weight: bold;",
        );
      }
      console.log("🏥 Assigned Clinic:", res.closest_location?.name);
      console.table(res.submitted_payload || payload);
      console.groupEnd();

      if (res.redirect_url) {
        window.location.href = res.redirect_url;
        return;
      }

      const highIncomeRanges = [
        "100000_124999",
        "125000_149999",
        "150000_199999",
        "200000_249999",
        "250000_plus",
      ];

      const isApproved =
        res.is_approved === true ||
        (res.estimated_income && Number(res.estimated_income) >= 100000) ||
        highIncomeRanges.includes(res.income_range_used);

      const clinic = res.closest_location || {
        name: "Jupiter - Life Imaging",
        address: "311 W Indiantown Rd.",
        city: "Jupiter",
        state: "FL",
        zip: "33458",
        phone: "954-834-6362",
      };

      const regionKey = getRegionKey(clinic, payload.zip_code);
      const regionData =
        REGIONS_DATABASE[regionKey] || REGIONS_DATABASE.jupiter;

      let resultHtml = "";

      if (isApproved) {
        // MATCH: >= 100,000 USD
        // Compute primary facility and a cleaned city line for display
        const primaryFac =
          regionData.options && regionData.options.length
            ? regionData.options.find((f) => /life\s*imaging/i.test(f.name)) ||
              regionData.options[0]
            : null;
        console.debug("[FHS] primaryFac selected:", primaryFac);
        let cityLine = "";
        if (regionData.matchLocation && regionData.matchLocation.address) {
          const parts = regionData.matchLocation.address.split(",");
          if (parts.length >= 2)
            cityLine = parts
              .slice(-2)
              .map((s) => s.trim())
              .join(", ");
          else cityLine = regionData.matchLocation.address;
        }

        // Prepare coupon URL (ensure absolute and encoded)
        const primaryCouponUrl =
          primaryFac && primaryFac.hasCoupon
            ? primaryFac.couponUrl
              ? encodeURI(primaryFac.couponUrl)
              : "https://www.groupon.com/deals/life-imaging-fla-1"
            : null;
        console.debug("[FHS] primaryCouponUrl:", primaryCouponUrl);

        resultHtml = `
          <div class="fhs-success-box fhs-match-box">
            <div class="fhs-badge fhs-badge-match">POTENTIAL FREE HEART SCAN MATCH</div>
            <h2 class="fhs-title-match">We Found a Potential Free Heart Scan Option Near You</h2>
            <p class="fhs-text-thankyou">Thank you for submitting your information.</p>
            <p class="fhs-text-desc">Based on the information provided, your request appears to match the initial criteria for a no-cost heart scan program available through a participating screening facility in your area.</p>
            
            <div class="fhs-card-location">
              <div class="fhs-location-label">Potential Screening Location</div>
              <div class="fhs-location-name">${primaryFac && /life\s*imaging/i.test(primaryFac.name) ? "Life Imaging" : primaryFac ? primaryFac.name : ""}</div>
              <div class="fhs-location-city">${regionData.matchLocation.title}</div>
              <div class="fhs-location-address">${primaryFac ? primaryFac.address.replace(/\n/g, "<br/>") : regionData.matchLocation.address}</div>
              <div class="fhs-location-cityline">${cityLine}</div>
              <div class="fhs-location-phone">Phone: <a href="tel:${primaryFac ? primaryFac.phone : regionData.matchLocation.phone}">${primaryFac ? primaryFac.phone : regionData.matchLocation.phone}</a></div>
              <div class="fhs-location-web">Website: <a href="${primaryFac ? primaryFac.websiteUrl : "https://lifeimagingfla.com"}" target="_blank" rel="noopener noreferrer">${primaryFac ? primaryFac.website : "lifeimagingfla.com"}</a></div>

              <!-- Action buttons removed for approved matches; scheduling team will contact the lead -->
            </div>

            <p class="fhs-note">A member of the scheduling team may contact you to review program requirements, availability, and next steps.</p>
            <p class="fhs-note-sub">Final eligibility and appointment availability are determined by the participating screening facility.</p>
            <p class="fhs-note-sub">Your information will only be used to process your request and help connect you with an appropriate screening provider, in accordance with our Privacy Policy.</p>
          </div>
        `;
      } else {
        // NO CURRENT MATCH: < 100,000 USD
        let facilitiesHtml = "";
        regionData.options.forEach(function (fac) {
          const isLifeImaging = /life\s*imaging/i.test(fac.name || "");
          const displayName = isLifeImaging
            ? "Life Imaging"
            : fac.name || "Facility";
          const displayAddress = fac.address
            ? fac.address.replace(/\n/g, "<br/>")
            : "";
          const displayPhone = fac.phone || "";
          const displayWebsite =
            fac.website ||
            (fac.websiteUrl ? new URL(fac.websiteUrl).hostname : "");
          const displayWebsiteUrl = fac.websiteUrl || "#";
          const displayCouponUrl = fac.hasCoupon
            ? isLifeImaging
              ? "https://www.groupon.com/deals/life-imaging-fla-1"
              : fac.couponUrl
                ? encodeURI(fac.couponUrl)
                : "https://www.groupon.com/deals/life-imaging-fla-1"
            : null;

          facilitiesHtml += `
            <div class="fhs-facility-card">
              <div class="fhs-facility-name">${displayName}</div>
              <div class="fhs-facility-address">${displayAddress}</div>
              ${displayPhone ? `<div class="fhs-facility-phone">Phone: <a href="tel:${displayPhone}">${displayPhone}</a></div>` : ""}
              <div class="fhs-facility-web">Website: <a href="${displayWebsiteUrl}" target="_blank" rel="noopener noreferrer">${displayWebsite}</a></div>
              <div class="fhs-facility-btns">
                <a href="${displayWebsiteUrl}" target="_blank" rel="noopener noreferrer" class="fhs-btn-visit">
                  Visit Website
                </a>
                ${
                  displayCouponUrl
                    ? `
                  <a href="${displayCouponUrl}" target="_blank" rel="noopener noreferrer" class="fhs-btn-coupon">
                    Get Budget-Friendly Coupon
                  </a>
                `
                    : ""
                }
              </div>
            </div>
          `;
        });

        resultHtml = `
          <div class="fhs-success-box fhs-no-match-box">
            <div class="fhs-badge fhs-badge-no-match">NO CURRENT FREE HEART SCAN MATCH</div>
            <h2 class="fhs-title-nomatch">Explore Other Heart Scan Options Near You</h2>
            <p class="fhs-text-thankyou">Thank you for submitting your information.</p>
            <p class="fhs-text-desc">Based on the information provided, we were not able to match your request with a current no-cost heart scan program available through our participating screening facilities.</p>
            <p class="fhs-text-desc">You may still have other heart scan options available in your area.<br/>Below are nearby facilities that may offer affordable or self-pay heart scan options.</p>

            <div class="fhs-options-section">
              <h3 class="fhs-options-header">Heart Scan Options Near You</h3>
              <div class="fhs-region-title">${regionData.matchLocation ? regionData.matchLocation.title : regionData.name}</div>
              <div class="fhs-facilities-list">
                ${facilitiesHtml}
              </div>
            </div>

            <p class="fhs-pricing-note">Pricing, availability, screening requirements, and eligibility are determined directly by each facility.</p>

            <div class="fhs-action-container">
              <a href="https://lifeimagingfla.com/lifeimaging-faq/" target="_blank" rel="noopener noreferrer" class="fhs-btn-action">
                Explore Heart Scan Options
              </a>
            </div>
          </div>
        `;
      }

      $wrapper.find(".fhs-progress-container").hide();
      $wrapper.find(".fhs-body").html(resultHtml);
      window.scrollTo({ top: $wrapper.offset().top - 40, behavior: "smooth" });
    }
  });
})(jQuery);
