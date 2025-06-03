# Contact Manager Backend Setup

## Environment Configuration

For security reasons, the OpenAI API key is now loaded from an environment variable instead of being hardcoded.

### Setting up the API Key

1. **Get your OpenAI API Key**
   - Go to https://platform.openai.com/api-keys
   - Create a new API key

2. **Set the Environment Variable**

   **Option 1: Terminal/Shell (for development)**
   ```bash
   export OPENAI_API_KEY="your_actual_api_key_here"
   ```

   **Option 2: Create a .env file (recommended)**
   Create a `.env` file in the `myApp` directory with:
   ```
   OPENAI_API_KEY=your_actual_api_key_here
   CI_ENVIRONMENT=development
   ```

   **Option 3: System Environment Variable**
   Set `OPENAI_API_KEY` as a system environment variable

### Running the Backend

1. Navigate to the backend directory:
   ```bash
   cd Contact-Manager-openAI\ copy\ 2/backend/myApp
   ```

2. Start the PHP development server:
   ```bash
   php spark serve --host localhost --port 8080
   ```

The backend will now run on http://localhost:8080 and securely load the API key from the environment variable.

### Important Notes

- Never commit API keys to version control
- The application will show an error if the API key is not configured
- Make sure your `.env` file is added to `.gitignore` to prevent accidental commits 