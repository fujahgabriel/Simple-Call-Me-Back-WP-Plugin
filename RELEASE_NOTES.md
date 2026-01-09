# 🚀 Release Notes: Simple Call Me Back v1.0.1

We are taking **Simple Call Me Back** to the next level with powerful integration capabilities and more design control. This release focuses on workflow automation and pixel-perfect design.

## 🌟 New Features in v1.0.1

### 🔗 HubSpot Integration (New!)
Transform your callback requests into actionable CRM data instantly.
*   **Automated Sync**: When a user submits a form, a Contact is automatically created in your HubSpot CRM.
*   **Smart Mapping**:
    *   Name -> First Name / Last Name
    *   Phone -> Phone Number
    *   Position -> Job Title
    *   Company -> Company Name
    *   Lifecycle Stage -> Lead
*   **Easy Setup**: Just enter your Private App Access Token in the settings.

### 📐 Precise Positioning (New!)
You asked for more control, we delivered.
*   **Custom Margins**: Beyond just choosing a "corner", you can now define the exact **X and Y margins** (in pixels).
*   **Perfect Alignment**: Ensure the button doesn't overlap with other chat widgets, GDPR banners, or "Scroll to Top" buttons.

---

# 🚀 Release Notes: Simple Call Me Back v1.0.0

We are excited to announce the initial release of **Simple Call Me Back**! This plugin is designed to help businesses capture high-quality leads by allowing website visitors to easily request a phone callback via a sleek, customizable modal.

## 🌟 Key Features

### 🎨 Frontend Experience
*   **Floating Action Button**: A sticky request button that remains visible as users scroll.
    *   Includes a clear phone SVG icon.
    *   Positionable in 4 corners: Bottom Right, Bottom Left, Top Right, Top Left.
*   **Smart Modal Form**: A responsive popup form with clean styling.
*   **International Phone Support**: Integrated `intl-tel-input` library provides a dropdown for country codes (with flags) and automatic number formatting.
*   **User Guidance**: Includes customizable header, subtext, and helpful input placeholders.

### ⚙️ Admin & Management
*   **Request Dashboard**: A dedicated admin page to view all incoming callback requests.
*   **Full CRUD Capabilities**:
    *   **View** detailed request lists.
    *   **Edit** request details and update lead status (New, Contacted, Closed).
    *   **Delete** spam or old requests.
*   **CSV Export**: One-click export functionality to take your data into Excel or other CRM tools.

### 🎨 Customization
*   **Visual Control**: Fully configurable colors for the main button, text, and submit buttons to match your brand identity.
*   **Text Control**: Customize the button label, modal title, and instructional subtext directly from the settings page.

## 🛠 Installation

1.  Upload the `simple-call-me-back` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Navigate to **Simple Call Me Back > Settings** to configure your preferences.
4.  Navigate to **Simple Call Me Back > Requests** to manage incoming leads.
