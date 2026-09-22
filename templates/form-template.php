<?php
/**
 * Free Heart Scan - Frontend Form Template
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="fhs-wrapper" id="fhs-form-container">
    <div class="fhs-header">
        <div class="fhs-badge">Florida Preventive Program</div>
        <h2 class="fhs-title">Free Heart Scan Preventive Evaluation</h2>
        <p class="fhs-subtitle">Complete your information to check your eligibility and assign your nearest medical center.</p>
    </div>

    <div class="fhs-progress-container">
        <div class="fhs-step-indicator">Step 1 of 2</div>
        <div class="fhs-progress-bar-bg">
            <div class="fhs-progress-bar-fill" style="width: 50%;"></div>
        </div>
        <div style="font-size: 11px; font-weight: 700; color: #15803d;">100% Confidential</div>
    </div>

    <div class="fhs-body">
        <form id="fhs-intake-form" novalidate>
            <!-- Step 1: Personal Information & Location -->
            <div class="fhs-step active" data-step="1">
                <div class="fhs-grid-2">
                    <div class="fhs-form-group">
                        <label class="fhs-label" for="fhs-first-name">First Name <span class="fhs-required">*</span></label>
                        <input type="text" id="fhs-first-name" class="fhs-input" placeholder="e.g. John" required />
                        <div class="fhs-field-error"></div>
                    </div>
                    <div class="fhs-form-group">
                        <label class="fhs-label" for="fhs-last-name">Last Name <span class="fhs-required">*</span></label>
                        <input type="text" id="fhs-last-name" class="fhs-input" placeholder="e.g. Smith" required />
                        <div class="fhs-field-error"></div>
                    </div>
                </div>

                <div class="fhs-grid-2">
                    <div class="fhs-form-group">
                        <label class="fhs-label" for="fhs-email">Email Address <span class="fhs-required">*</span></label>
                        <input type="email" id="fhs-email" class="fhs-input" placeholder="john@example.com" required />
                        <div class="fhs-field-error"></div>
                    </div>
                    <div class="fhs-form-group">
                        <label class="fhs-label" for="fhs-phone">Mobile Phone <span class="fhs-required">*</span></label>
                        <input type="tel" id="fhs-phone" class="fhs-input" placeholder="(305) 555-0199" required />
                        <div class="fhs-field-error"></div>
                    </div>
                </div>

                <div class="fhs-grid-2">
                    <div class="fhs-form-group">
                        <label class="fhs-label" for="fhs-zip">ZIP Code <span class="fhs-required">*</span></label>
                        <input type="text" id="fhs-zip" class="fhs-input" placeholder="33134" maxlength="5" required />
                        <div class="fhs-field-error"></div>
                    </div>
                    <div class="fhs-form-group">
                        <label class="fhs-label" for="fhs-age">Age Range <span class="fhs-required">*</span></label>
                        <select id="fhs-age" class="fhs-select" required>
                            <option value="">Select your age...</option>
                            <option value="30_39">30 - 39 years</option>
                            <option value="40_49">40 - 49 years</option>
                            <option value="50_59">50 - 59 years</option>
                            <option value="60_69">60 - 69 years</option>
                            <option value="70_plus">70+ years</option>
                            <option value="under_30">Under 30 years</option>
                        </select>
                        <div class="fhs-field-error"></div>
                    </div>
                </div>

                <div class="fhs-footer">
                    <div></div>
                    <button type="button" class="fhs-btn fhs-btn-primary fhs-btn-next">
                        Continue to Step 2 →
                    </button>
                </div>
            </div>

            <!-- Step 2: Occupational & Stress Profile -->
            <div class="fhs-step" data-step="2">
                <div class="fhs-grid-2">
                    <div class="fhs-form-group">
                        <label class="fhs-label" for="fhs-stress">Work-Related Stress Level <span class="fhs-required">*</span></label>
                        <select id="fhs-stress" class="fhs-select" required>
                            <option value="">Select level...</option>
                            <option value="very_low">Very Low</option>
                            <option value="low">Low</option>
                            <option value="moderate" selected>Moderate</option>
                            <option value="high">High</option>
                            <option value="very_high">Very High / Critical</option>
                        </select>
                        <div class="fhs-field-error"></div>
                    </div>

                    <div class="fhs-form-group">
                        <label class="fhs-label" for="fhs-years">Years in Position <span class="fhs-required">*</span></label>
                        <select id="fhs-years" class="fhs-select" required>
                            <option value="">Select experience...</option>
                            <option value="less_than_1">Less than 1 year</option>
                            <option value="1_2">1 to 2 years</option>
                            <option value="3_5">3 to 5 years</option>
                            <option value="6_10">6 to 10 years</option>
                            <option value="11_15">11 to 15 years</option>
                            <option value="16_20">16 to 20 years</option>
                            <option value="20_plus">More than 20 years</option>
                        </select>
                        <div class="fhs-field-error"></div>
                    </div>
                </div>

                <div class="fhs-form-group">
                    <label class="fhs-label" for="fhs-job-title">Current Job Title / Occupation <span class="fhs-required">*</span></label>
                    <input type="text" id="fhs-job-title" class="fhs-input" placeholder="e.g. Accountant, Nurse, Pilot, Engineer, Manager..." required />
                    <div class="fhs-field-error"></div>
                </div>

                <div class="fhs-grid-2">
                    <div class="fhs-form-group">
                        <label class="fhs-label" for="fhs-employment">Employment Status</label>
                        <select id="fhs-employment" class="fhs-select">
                            <option value="employed" selected>Employed full-time</option>
                            <option value="self_employed">Self-employed / Business Owner</option>
                            <option value="retired">Retired</option>
                            <option value="part_time">Part-time</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="fhs-form-group">
                        <label class="fhs-label" for="fhs-industry">Industry / Sector</label>
                        <select id="fhs-industry" class="fhs-select">
                            <option value="healthcare">Healthcare / Medical</option>
                            <option value="technology">Technology / IT</option>
                            <option value="finance">Finance / Banking</option>
                            <option value="education">Education</option>
                            <option value="transportation">Transportation / Aviation</option>
                            <option value="legal">Legal</option>
                            <option value="real_estate">Real Estate</option>
                            <option value="construction">Construction</option>
                            <option value="hospitality">Hospitality / Services</option>
                            <option value="other" selected>Other Sector</option>
                        </select>
                    </div>
                </div>

                <div class="fhs-footer">
                    <button type="button" class="fhs-btn fhs-btn-secondary fhs-btn-prev">
                        ← Back to Step 1
                    </button>
                    <button type="submit" class="fhs-btn fhs-btn-primary fhs-btn-submit">
                        Check Eligibility & Submit Request ✓
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
