class ApiConfig {
  ApiConfig._();

  static const baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api/v1',
  );

  static const appVersion = String.fromEnvironment(
    'APP_VERSION',
    defaultValue: '1.3.0',
  );
}
