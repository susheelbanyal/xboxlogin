# xboxlogin
Login with xbox Account on web PHP

# Sign in to Xbox Live with OAUTH2

1. Go to https://portal.azure.com/#blade/Microsoft_AAD_RegisteredApps/ApplicationsListBlade
2. Register new app ("+ New registration")
2.1. Enter a name for your app
2.2. Set "Supported account types" to "Personal Microsoft accounts only"
2.3. Click register
2.4. Choose "Redirect URIs" -> "Add a Redirect URI"
2.5. Click "Add a platform" -> "Mobile and desktop applications"
2.6. Enter custom redirect URI (Use something like "https://localhost/oauth_success" for testing)
3. From the overview of your app page, copy "Application (client) ID" to CLIENT_ID below in the py code
4. Replace REDIRECT_URI in the py code with the actual URI set in Azure app registration
5. Test and enjoy;)

