import 'package:flutter/material.dart';
import 'screens/announcements/announcement_screen.dart';
void main() => runApp(const BrewBoxApp());
class BrewBoxApp extends StatelessWidget {const BrewBoxApp({super.key}); @override Widget build(BuildContext context)=>MaterialApp(title:'THE BREW BOX',theme:ThemeData(colorSchemeSeed:Colors.brown,useMaterial3:true),home:const AnnouncementScreen());}
