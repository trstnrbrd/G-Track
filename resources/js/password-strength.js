/**
 * How strong a password looks, for the little bar under every new-password
 * field (components/password-strength.blade.php).
 *
 * This is advice, not a rule: the server only requires 8 characters
 * (Password::defaults()). The bar just nudges people past "12345678".
 *
 * Returns { level: 0-4, tone: 'none'|'red'|'amber'|'blue', label, hint }.
 * Tones are mapped to colours in the Blade component, because Tailwind only
 * emits classes it can see in the views, not in this file.
 */
export const MIN_LENGTH = 8;

export function passwordStrength(value) {
    const text = value || '';
    const length = text.length;

    if (length === 0) {
        return { level: 0, tone: 'none', label: '', hint: '' };
    }

    if (length < MIN_LENGTH) {
        const left = MIN_LENGTH - length;

        return {
            level: 1,
            tone: 'red',
            label: 'Too short',
            hint: left + ' more ' + (left === 1 ? 'character' : 'characters') + ' to go',
        };
    }

    const hasMixedCase = /[a-z]/.test(text) && /[A-Z]/.test(text);
    const hasDigit = /\d/.test(text);
    const hasSymbol = /[^A-Za-z0-9]/.test(text);

    let points = 0;
    if (length >= 12) points++;
    if (length >= 16) points++;
    if (hasMixedCase) points++;
    if (hasDigit) points++;
    if (hasSymbol) points++;

    // Patterns that make a long-looking password easy to guess.
    const guessable = /(password|passw0rd|qwerty|asdf|12345|abc123|letmein|iloveyou|admin|welcome|gtrack|gcash)/i.test(text);
    const repeated = /(.)\1{3,}/.test(text);
    if (guessable) points -= 2;
    if (repeated) points -= 2;

    // The first thing worth fixing, said plainly.
    let hint = '';
    if (guessable) hint = 'Avoid common words and runs like "password" or "12345".';
    else if (repeated) hint = 'Avoid repeating the same character.';
    else if (! hasMixedCase) hint = 'Mix upper and lower case.';
    else if (! hasDigit) hint = 'Add a number.';
    else if (! hasSymbol) hint = 'Add a symbol like ! or @.';
    else if (length < 12) hint = 'Make it 12 or more characters.';

    if (points <= 1) return { level: 1, tone: 'red', label: 'Weak', hint };
    if (points === 2) return { level: 2, tone: 'amber', label: 'Fair', hint };
    if (points === 3) return { level: 3, tone: 'blue', label: 'Good', hint };

    return { level: 4, tone: 'blue', label: 'Strong', hint: '' };
}
