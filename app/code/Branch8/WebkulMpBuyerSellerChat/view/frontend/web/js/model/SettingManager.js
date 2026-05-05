/*********************************
 store chat settings at localstorage
 *******************************/
define([], function () {
    const defaultSettings = {
        'soundEnabled': true,
    }

    class SettingsManager {
        constructor(storageKey) {
            this.storageKey = storageKey;
            this.settings = this.loadSettings(); // Load settings from localStorage during initialization
        }

        /**
         * loadSettings
         * @returns {{}|any|{}}
         */
        loadSettings() {
            try {
                const settingsString = localStorage.getItem(this.storageKey);
                return settingsString ? JSON.parse(settingsString) : {};
            } catch (error) {
                console.error('Error loading settings from localStorage', error);
                return {};
            }
        }

        /**
         * setSettings
         * @param settings
         */
        setSettings(settings) {
            try {
                localStorage.setItem(this.storageKey, JSON.stringify(settings));
            } catch (error) {
                console.error('Error saving settings to localStorage', error);
            }
        }

        /**
         * getSettings
         * @returns {{}|any|{}}
         */
        // Method to get the entire settings object from localStorage
        getSettings() {
            try {
                const settingsString = localStorage.getItem(this.storageKey);
                return settingsString ? JSON.parse(settingsString) : {};
            } catch (error) {
                console.error('Error retrieving settings from localStorage', error);
                return {};
            }
        }

        /**
         * getSetting
         * @param key
         * @returns {*}
         */
        getSetting(key) {
            const settings = this.getSettings();
            return settings[key];
        }

        /**
         * setSetting
         * @param key
         * @param value
         */
        setSetting(key, value) {
            const settings = this.getSettings();
            settings[key] = value;
            this.setSettings(settings);
        }

        /**
         * removeSetting
         * @param key
         */
        removeSetting(key) {
            if (key in this.settings) {
                delete this.settings[key];
                this.saveSettings(); // Save the updated settings back to localStorage
            }
        }

        /**
         * removeSettings
         */
        removeSettings() {
            try {
                localStorage.removeItem(this.storageKey);
            } catch (error) {
                console.error('Error removing settings from localStorage', error);
            }
        }
    }

    return new SettingsManager('chatSettings');
});
