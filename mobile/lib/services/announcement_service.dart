import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/announcement_model.dart';
class AnnouncementService {
 // Android emulator: 10.0.2.2; iOS simulator: localhost; physical device: LAN IP.
 static const baseUrl=String.fromEnvironment('API_BASE_URL',defaultValue:'http://10.0.2.2:8000/api');
 Future<List<Announcement>> fetchAnnouncements() async {
 final response=await http.get(Uri.parse('$baseUrl/announcements'));
 if(response.statusCode!=200)throw Exception('Could not load announcements (${response.statusCode})');
 final decoded=jsonDecode(response.body) as Map<String,dynamic>;
 return (decoded['data'] as List).map((e)=>Announcement.fromJson(e as Map<String,dynamic>)).toList();
 }
}
