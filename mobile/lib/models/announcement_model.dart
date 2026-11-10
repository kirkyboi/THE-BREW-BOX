class Announcement {
 final int id; final String title; final String content; final String? image;
 Announcement({required this.id,required this.title,required this.content,this.image});
 factory Announcement.fromJson(Map<String,dynamic> json)=>Announcement(id:json['id'] as int,title:json['title'] as String,content:json['content'] as String,image:json['image'] as String?);
}
