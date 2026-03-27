// mobile/lib/screens/home_screen.dart
import 'dart:ui';
import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import 'salon_detail_screen.dart';
import 'appointments_screen.dart';
import 'notifications_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _selectedIndex = 0;
  List<dynamic> _salons = [];
  String _selectedCategory = 'all';
  bool _isLoading = true;

  final List<Widget> _screens = [
    const SizedBox(), // Placeholder for Explore (dynamic content)
    const AppointmentsScreen(),
    const NotificationsScreen(),
  ];

  @override
  void initState() {
    super.initState();
    _fetchSalons();
  }

  String _searchQuery = '';

  void _fetchSalons() async {
    try {
      final response = await ApiService.get('salons.php');
      if (response['status'] == 200) {
        setState(() {
          _salons = response['data'];
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  List<dynamic> get _filteredSalons {
    return _salons.where((s) {
      final matchesCategory = _selectedCategory == 'all' || s['type'] == _selectedCategory;
      final matchesSearch = _searchQuery.isEmpty || 
          s['name'].toString().toLowerCase().contains(_searchQuery.toLowerCase()) ||
          s['city'].toString().toLowerCase().contains(_searchQuery.toLowerCase());
      return matchesCategory && matchesSearch;
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      extendBodyBehindAppBar: true,
      body: Stack(
        children: [
          Container(decoration: AppTheme.meshGradient),
          IndexedStack(
            index: _selectedIndex,
            children: [
              _buildExploreScreen(),
              const AppointmentsScreen(),
              const NotificationsScreen(),
            ],
          ),
        ],
      ),
      bottomNavigationBar: _buildBottomNav(),
    );
  }

  Widget _buildBottomNav() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.black.withOpacity(0.8),
        border: const Border(top: BorderSide(color: Colors.white10)),
      ),
      child: BottomNavigationBar(
        currentIndex: _selectedIndex,
        onTap: (index) => setState(() => _selectedIndex = index),
        backgroundColor: Colors.transparent,
        elevation: 0,
        selectedItemColor: AppTheme.primaryColor,
        unselectedItemColor: Colors.white30,
        type: BottomNavigationBarType.fixed,
        items: const [
          BottomNavigationBarItem(icon: Icon(Icons.explore_outlined), activeIcon: Icon(Icons.explore), label: 'Keşfet'),
          BottomNavigationBarItem(icon: Icon(Icons.calendar_today_outlined), activeIcon: Icon(Icons.calendar_today), label: 'Randevularım'),
          BottomNavigationBarItem(icon: Icon(Icons.notifications_outlined), activeIcon: Icon(Icons.notifications), label: 'Bildirimler'),
        ],
      ),
    );
  }

  Widget _buildExploreScreen() {
    return SafeArea(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Custom Header
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 20),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Hoş Geldiniz,', style: TextStyle(color: Colors.white60, fontSize: 14)),
                    SizedBox(height: 4),
                    Text('SALON KEŞFET', style: TextStyle(fontSize: 24, fontWeight: FontWeight.w900, letterSpacing: 1.2)),
                  ],
                ),
                GestureDetector(
                  onTap: () {
                    // Fokuslanma veya modal açma yapılabilir
                  },
                  child: Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.05),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: const Icon(Icons.filter_list, color: AppTheme.primaryColor),
                  ),
                ),
              ],
            ),
          ),

          // Search Box
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
            child: TextField(
              onChanged: (val) => setState(() => _searchQuery = val),
              style: const TextStyle(color: Colors.white),
              decoration: InputDecoration(
                hintText: 'Berber veya şehir ara...',
                hintStyle: TextStyle(color: Colors.white.withOpacity(0.3)),
                prefixIcon: const Icon(Icons.search, color: AppTheme.primaryColor, size: 20),
                filled: true,
                fillColor: Colors.white.withOpacity(0.05),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide.none),
              ),
            ),
          ),
          
          const SizedBox(height: 10),
          // Categories
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Row(
              children: [
                _buildCategoryChip('Tümü', 'all', Icons.grid_view),
                _buildCategoryChip('Erkek', 'male', Icons.face),
                _buildCategoryChip('Kadın', 'female', Icons.face_retouching_natural),
                _buildCategoryChip('Çocuk', 'child', Icons.child_care),
              ],
            ),
          ),
          
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 25, 20, 15),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Öne Çıkan Salonlar',
                  style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                ),
                Text(
                  '${_filteredSalons.length} Sonuç',
                  style: TextStyle(color: Colors.white30, fontSize: 12),
                ),
              ],
            ),
          ),
          
          Expanded(
            child: _isLoading
              ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryColor))
              : _filteredSalons.isEmpty
                ? const Center(child: Text('Aradığınız kriterde salon bulunamadı.', style: TextStyle(color: Colors.white30)))
                : ListView.builder(
                    padding: const EdgeInsets.symmetric(horizontal: 20),
                    itemCount: _filteredSalons.length,
                    itemBuilder: (context, index) {
                      final salon = _filteredSalons[index];
                      return _buildSalonCard(salon);
                    },
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildCategoryChip(String label, String value, IconData icon) {
    final isSelected = _selectedCategory == value;
    return Padding(
      padding: const EdgeInsets.only(right: 12),
      child: GestureDetector(
        onTap: () => setState(() => _selectedCategory = value),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
          decoration: BoxDecoration(
            color: isSelected ? AppTheme.primaryColor : Colors.white.withOpacity(0.05),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: isSelected ? Colors.transparent : Colors.white10),
          ),
          child: Row(
            children: [
              Icon(icon, size: 18, color: isSelected ? Colors.white : Colors.white60),
              const SizedBox(width: 8),
              Text(
                label,
                style: TextStyle(
                  color: isSelected ? Colors.white : Colors.white60,
                  fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSalonCard(dynamic salon) {
    return GestureDetector(
      onTap: () => Navigator.of(context).push(
        MaterialPageRoute(builder: (_) => SalonDetailScreen(salonId: int.parse(salon['id'].toString()))),
      ),
      child: Container(
        margin: const EdgeInsets.only(bottom: 20),
        decoration: AppTheme.glassDecoration,
        child: ClipRRect(
          borderRadius: BorderRadius.circular(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Stack(
                children: [
                  Container(
                    height: 180,
                    width: double.infinity,
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.05),
                      image: DecorationImage(
                        image: salon['cover_image'] != null 
                          ? NetworkImage('${ApiService.baseMediaUrl}${salon['cover_image']}')
                          : const NetworkImage('https://via.placeholder.com/800x400'),
                        fit: BoxFit.cover,
                      ),
                    ),
                  ),
                  Positioned(
                    top: 15,
                    right: 15,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                      decoration: BoxDecoration(
                        color: Colors.black.withOpacity(0.6),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.white10),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.star, color: Colors.amber, size: 14),
                          const SizedBox(width: 4),
                          Text(
                            salon['avg_rating']?.toString() ?? 'Yeni',
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
              Padding(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Expanded(
                          child: Text(
                            salon['name'].toString().toUpperCase(),
                            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, letterSpacing: 1),
                          ),
                        ),
                        const Icon(Icons.arrow_forward_ios, size: 14, color: Colors.white30),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        const Icon(Icons.location_on, size: 14, color: AppTheme.primaryColor),
                        const SizedBox(width: 4),
                        Text(
                          '${salon['city']} / ${salon['district'] ?? ''}',
                          style: TextStyle(color: Colors.white.withOpacity(0.6), fontSize: 13),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
