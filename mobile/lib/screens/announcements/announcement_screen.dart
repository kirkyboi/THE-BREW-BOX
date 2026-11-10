import 'package:flutter/material.dart';
import '../../models/announcement_model.dart';
import '../../services/announcement_service.dart';
import 'announcement_details_screen.dart';
class AnnouncementScreen extends StatefulWidget {const AnnouncementScreen({super.key}); @override State<AnnouncementScreen> createState()=>_AnnouncementScreenState();}
class _AnnouncementScreenState extends State<AnnouncementScreen> {
 late Future<List<Announcement>> future;
 @override void initState(){super.initState();future=AnnouncementService().fetchAnnouncements();}
 @override Widget build(BuildContext context)=>Scaffold(appBar:AppBar(title:const Text('THE BREW BOX • Announcements')),body:FutureBuilder<List<Announcement>>(future:future,builder:(context,snapshot){if(snapshot.connectionState!=ConnectionState.done)return const Center(child:CircularProgressIndicator());if(snapshot.hasError)return Center(child:Text('${snapshot.error}'));final items=snapshot.data??[];if(items.isEmpty)return const Center(child:Text('No announcements yet'));return ListView.builder(itemCount:items.length,itemBuilder:(context,index){final item=items[index];return Card(child:ListTile(title:Text(item.title),subtitle:Text(item.content,maxLines:2,overflow:TextOverflow.ellipsis),trailing:const Icon(Icons.chevron_right),onTap:()=>Navigator.push(context,MaterialPageRoute(builder:(_)=>AnnouncementDetailsScreen(announcement:item)))));});}));
}
