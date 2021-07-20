# xboxlogin
Login with xbox Account on web PHP

# Sign in to Xbox Live with OAUTH2

1. Go to https://portal.azure.com/#blade/Microsoft_AAD_RegisteredApps/ApplicationsListBlade
2. Register new app ("+ New registration")
3. Enter a name for your app
4. Set "Supported account types" to "Personal Microsoft accounts only"
5. Click register
6. Choose "Redirect URIs" -> "Add a Redirect URI"
7. Click "Add a platform" -> "Mobile and desktop applications"
8. Enter custom redirect URI (Use something like "https://localhost/success.php" for testing)
9. From the overview of your app page, copy "Application (client) ID" to CLIENT_ID below in the py code
10. Replace REDIRECT_URI in the py code with the actual URI set in Azure app registration
11. Test and enjoy;)

