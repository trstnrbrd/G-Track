import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
    ],

    theme: {
        extend: {
            // The ONLY place the app's fonts are defined. Tailwind's base styles
            // apply `sans` to the whole page, so nothing else should name a font.
            // Loaded from Google Fonts in layouts/app.blade.php and guest.blade.php.
            fontFamily: {
                // All UI text.
                sans: ["Inter", ...defaultTheme.fontFamily.sans],

                // Reference numbers and mobile numbers only (`font-mono`). Fixed
                // width, so long digit strings are easy to read back to a customer.
                mono: ["Geist Mono", ...defaultTheme.fontFamily.mono],
            },

            // Brand colors. Each one MEANS something — never use them for
            // decoration. Anything that isn't GCash or cash stays neutral gray.
            // 600 is the base; white text on it passes WCAG AA.
            colors: {
                // The page background behind every card: a warm off-white, so
                // white cards read as paper sitting on it rather than dissolving
                // into a white page. Darken it slightly for more contrast.
                canvas: "#F3F2EE",

                // Cards, the sidebar, and the top bar. A warm white rather than
                // pure #FFFFFF: pure white is cool, and sitting on the warm canvas
                // it reads as a different temperature — a clash you feel before
                // you can name it. Keep this and `canvas` in the same warm family.
                surface: "#FBFAF7",

                // GCash: the wallet balance, the Cash Out action, and the app's
                // single UI accent (primary buttons, active nav, links, live state).
                gcash: {
                    50: "#EEF3FE",
                    100: "#DCE6FD",
                    200: "#BACDFB",
                    300: "#8FADF7",
                    400: "#5E88F1",
                    500: "#3A6DEA",
                    600: "#1F5AE0",
                    700: "#1848B8",
                    800: "#163C93",
                    900: "#152F6E",
                },
                // Physical cash: the drawer balance and the Cash In action.
                // Not a generic "success" green.
                cash: {
                    50: "#ECF7F1",
                    100: "#D3EEDF",
                    200: "#A7DCC0",
                    300: "#72C39B",
                    400: "#3FA675",
                    500: "#1E8C58",
                    600: "#127A49",
                    700: "#0F633C",
                    800: "#0D4F31",
                    900: "#0B3F28",
                },
            },
        },
    },

    plugins: [forms],
};
