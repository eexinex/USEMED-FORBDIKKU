---
name: USE MED
description: Medical AI System
colors:
  primary: "#0f9f85"
  primary-dark: "#087662"
  primary-soft: "#e8f8f4"
  neutral-bg: "#f4fbfa"
  neutral-bg2: "#eef7ff"
  neutral-white: "#ffffff"
  neutral-ink: "#102a27"
  neutral-muted: "#667b77"
  neutral-line: "#dcebe7"
typography:
  body:
    fontFamily: '"Segoe UI", "Noto Sans Thai", Tahoma, Arial, sans-serif'
    lineHeight: 1.6
rounded:
  default: "22px"
  sm: "16px"
spacing:
  sm: "8px"
  md: "12px"
  lg: "16px"
  xl: "22px"
  xxl: "28px"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.neutral-white}"
    rounded: "{rounded.sm}"
    padding: "11px 16px"
  card:
    backgroundColor: "{colors.neutral-white}"
    rounded: "{rounded.default}"
    padding: "22px"
---

# Design System: USE MED

## 1. Overview

**Creative North Star: "The Clinical Navigator"**

This design system is built to be calm, precise, efficient, and data-forward. It is a robust, trustworthy dashboard for medical staff to interact with AI diagnostics and manage medical workflows. The aesthetic philosophy emphasizes clarity over ornament, ensuring that users feel confident and supported during critical tasks. This system explicitly rejects cluttered, overly-complex clinical legacy software, generic SaaS "cream/warm" aesthetics, and playful or consumer-style interfaces.

**Key Characteristics:**
- **Clarity over ornament**: Precise, data-forward, and legible.
- **Trust through stability**: Predictable layouts conveying clinical confidence.
- **Expert focus**: Streamlined workflows focusing on required data without oversimplification.

## 2. Colors

The palette is rooted in a clinical, Classic Medical Green, balanced by crisp whites and deep ink neutrals for high legibility.

### Primary
- **Classic Medical Green** (#0f9f85): The core brand and action color. Used for primary buttons, active states, and key highlights.
- **Medical Green Dark** (#087662): Used for hover states and subtle gradients to provide depth.
- **Medical Green Soft** (#e8f8f4): Used as a subtle background for badges, active navigation items, and highlighted rows.

### Neutral
- **Deep Ink** (#102a27): The primary text color for maximum contrast and legibility.
- **Muted Slate** (#667b77): Used for secondary text, supportive copy, and less critical UI elements.
- **Clean White** (#ffffff): Used for cards, panels, and primary surface backgrounds.
- **Cool Background** (#f4fbfa / #eef7ff): The ambient background behind cards to help panels stand out.
- **Subtle Line** (#dcebe7): Used for borders, dividers, and structural separation.

### Named Rules
**The Clinical Canvas Rule.** The background should remain a cool, tinted neutral (`#f4fbfa`), avoiding any generic warm or cream colors. The interface must feel sterile, precise, and professional.

## 3. Typography

**Body Font:** "Segoe UI", "Noto Sans Thai", Tahoma, Arial, sans-serif

**Character:** A highly legible, system-native sans-serif stack that prioritizes clarity and immediate readability across both English and Thai scripts.

### Hierarchy
- **Body** (400, 1rem, 1.6): Primary text for all interface copy, forms, and tables.
- **Heading 1 / Topbar** (800, clamp(28px, 4vw, 42px), 1.02): Used for page titles and major structural anchors.
- **Heading 2 / Card Title** (800, 20px, 1.2): Used for card headers and section titles.
- **Label / Small** (800, 13px-14px, normal): Used for badges, small links, and form labels.

### Named Rules
**The Legibility First Rule.** All text must prioritize clarity. Avoid all-caps for long strings and ensure body copy remains highly readable under various clinical lighting conditions.

## 4. Elevation

The system is flat-by-default with soft, diffuse shadows used primarily for focus, modals, and dropdowns. Surfaces rely mostly on borders and soft background tints for separation.

### Shadow Vocabulary
- **Soft Lift** (`box-shadow: 0 10px 28px rgba(8, 72, 63, 0.09)`): Applied to cards and panels to subtly lift them from the background.
- **Focus Shadow** (`box-shadow: 0 22px 60px rgba(8, 72, 63, 0.14)`): Used for hovering interactive elements like buttons or floating panels.

### Named Rules
**The Flat-by-Default Rule.** Surfaces are generally flat and rely on `#dcebe7` borders. Shadows are reserved for interactive lift (hover) or separating critical overlay content.

## 5. Components

Components are soft, friendly, and accessible, using generous border radii to contrast the clinical precision of the data they hold.

### Buttons
- **Shape:** Softly rounded pill-like edges (16px radius).
- **Primary:** Linear gradient from Primary to Primary-Dark, white text, padding 11px 16px.
- **Hover / Focus:** Lifts slightly (`translateY(-2px)`) with reduced brightness.
- **Secondary:** White background with Deep Ink text and a Subtle Line border.

### Cards / Containers
- **Corner Style:** Highly rounded (22px radius).
- **Background:** Clean White (`#ffffff`), often with slight transparency (`0.92`).
- **Shadow Strategy:** Soft Lift for standard cards.
- **Border:** Subtle Line (`1px solid var(--line)`).
- **Internal Padding:** Generous (22px - 24px).

### Inputs / Fields
- **Style:** Clean White background, Subtle Line border, rounded (16px radius).
- **Focus:** Border shifts to a soft teal with a subtle focus ring (`box-shadow: 0 0 0 5px rgba(15, 159, 133, 0.11)`).

### Navigation
- **Style:** Sidebar navigation with highly rounded items (17px radius). Active states use a soft white translucent background and a slight translation (`translateX(3px)`).

## 6. Do's and Don'ts

### Do:
- **Do** maintain a strict, data-forward aesthetic using Deep Ink (`#102a27`) for all primary text.
- **Do** use generously rounded corners (16px-22px) to make the clinical interface feel soft and accessible.
- **Do** rely on the Classic Medical Green (`#0f9f85`) for primary actions and active states.

### Don't:
- **Don't** use generic SaaS "cream/warm" aesthetics for the background.
- **Don't** build cluttered, overly-complex clinical legacy layouts. Maintain padding and whitespace.
- **Don't** use playful or consumer-style interfaces, such as bouncy animations or overly bright secondary colors.
