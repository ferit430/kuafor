// mobile/lib/services/api_service.dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  static const String baseUrl = 'https://kuafor-ockqhgftm-feritdemirinfo-9308s-projects.vercel.app/backend/api';
  static const String baseMediaUrl = 'https://kuafor-ockqhgftm-feritdemirinfo-9308s-projects.vercel.app/';
  static String? token;

  static Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    token = prefs.getString('auth_token');
    print('ApiService Initialized. Token found: ${token != null}');
  }

  static Future<void> setToken(String newToken) async {
    token = newToken;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', newToken);
    print('ApiService Token Saved: ${newToken.substring(0, 10)}...');
  }

  static Future<void> clearToken() async {
    token = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    print('ApiService Token Cleared');
  }

  static Future<Map<String, dynamic>> post(String endpoint, Map<String, dynamic> data) async {
    print('ApiService POST: $endpoint (Has Token: ${token != null})');
    final response = await http.post(
      Uri.parse('$baseUrl/$endpoint'),
      headers: {
        'Content-Type': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      },
      body: jsonEncode(data),
    );
    print('ApiService Response (${response.statusCode}): ${response.body}');
    return jsonDecode(response.body);
  }

  static Future<Map<String, dynamic>> get(String endpoint, {Map<String, String>? params}) async {
    final uri = Uri.parse('$baseUrl/$endpoint').replace(queryParameters: params);
    final response = await http.get(
      uri,
      headers: {
        if (token != null) 'Authorization': 'Bearer $token',
      },
    );
    return jsonDecode(response.body);
  }
}
